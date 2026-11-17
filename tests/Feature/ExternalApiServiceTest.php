<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Position;
use App\Models\Project;
use App\Services\ExternalApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use JsonException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExternalApiServiceTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'https://api.example.com/entries';
    private const LOGIN_URL = 'https://api.example.com/login';
    private const BODY = '{"date": {date}, "hours": {hours}, "comment": {description}, "ref": {project_reference}}';

    #[Test]
    public function it_renders_all_placeholders_as_json_values(): void
    {
        $invoice = $this->makeInvoice([], ['title' => 'Website']);
        $position = Position::factory()->for($invoice)->create([
            'started_at' => '2026-03-01 09:00:00',
            'finished_at' => '2026-03-01 17:30:00',
            'pause_duration' => 0.5,
            'description' => "Fixed \"quoted\" bug\nand more",
            'remote' => true,
        ]);
        $template = '{"date": {date}, "start": {started_at}, "end": {finished_at}, "hours": {hours},'
            . ' "pause": {pause}, "text": {description}, "remote": {remote}, "title": {invoice_title},'
            . ' "ref": {project_reference}}';

        $body = ExternalApiService::renderBody($position, $template);

        $this->assertSame([
            'date' => '2026-03-01',
            'start' => $position->started_at->toIso8601String(),
            'end' => $position->finished_at->toIso8601String(),
            'hours' => 8,
            'pause' => 0.5,
            'text' => "Fixed \"quoted\" bug\nand more",
            'remote' => true,
            'title' => 'Website',
            'ref' => '42',
        ], $body);
    }

    #[Test]
    public function it_fails_on_a_template_that_is_no_valid_json(): void
    {
        $position = Position::factory()->for($this->makeInvoice())->create();

        $this->expectException(JsonException::class);

        ExternalApiService::renderBody($position, '{"date": {date}');
    }

    #[Test]
    public function it_pushes_unsent_positions_and_stores_their_external_ids(): void
    {
        Http::fake([self::URL => Http::sequence()->push(['id' => 7], 201)->push(['id' => 8], 201)]);
        $invoice = $this->makeInvoice();
        $positions = Position::factory()->for($invoice)->count(2)->create();

        $result = ExternalApiService::pushInvoice($invoice);

        $this->assertSame(['sent' => 2, 'errors' => []], $result);
        $this->assertSame(['7', '8'], $positions->map(fn(Position $p) => $p->fresh()->external_id)->sort()->values()->all());
        Http::assertSentCount(2);
        Http::assertSent(fn(Request $request) => $request->method() === 'POST'
            && $request->url() === self::URL
            && $request['ref'] === '42'
            && $request['comment'] === $positions->first()->description);
    }

    #[Test]
    public function it_skips_positions_that_were_already_sent(): void
    {
        Http::fake([self::URL => Http::response(['id' => 9], 201)]);
        $invoice = $this->makeInvoice();
        Position::factory()->for($invoice)->create(['external_id' => '1']);
        $unsent = Position::factory()->for($invoice)->create();

        $result = ExternalApiService::pushInvoice($invoice);

        $this->assertSame(1, $result['sent']);
        $this->assertSame('9', $unsent->fresh()->external_id);
        Http::assertSentCount(1);
    }

    #[Test]
    public function it_uses_the_configured_method_and_id_field(): void
    {
        Http::fake([self::URL => Http::response(['data' => ['uuid' => 'abc']])]);
        $invoice = $this->makeInvoice(['method' => 'PUT', 'id_field' => 'data.uuid']);
        $position = Position::factory()->for($invoice)->create();

        ExternalApiService::pushInvoice($invoice);

        $this->assertSame('abc', $position->fresh()->external_id);
        Http::assertSent(fn(Request $request) => $request->method() === 'PUT');
    }

    #[Test]
    public function it_sends_a_static_bearer_token(): void
    {
        Http::fake([self::URL => Http::response(['id' => 1])]);
        $invoice = $this->makeInvoice(['auth' => 'token', 'token' => 'secret']);
        Position::factory()->for($invoice)->create();

        ExternalApiService::pushInvoice($invoice);

        Http::assertSent(fn(Request $request) => $request->hasHeader('Authorization', 'Bearer secret'));
    }

    #[Test]
    public function it_sends_basic_auth_credentials(): void
    {
        Http::fake([self::URL => Http::response(['id' => 1])]);
        $invoice = $this->makeInvoice(['auth' => 'basic', 'username' => 'me', 'password' => 'pw']);
        Position::factory()->for($invoice)->create();

        ExternalApiService::pushInvoice($invoice);

        Http::assertSent(fn(Request $request) => $request->hasHeader('Authorization', 'Basic ' . base64_encode('me:pw')));
    }

    #[Test]
    public function it_logs_in_once_and_uses_the_returned_token(): void
    {
        Http::fake([
            self::LOGIN_URL => Http::response(['jwt' => 'fresh-token']),
            self::URL => Http::response(['id' => 1]),
        ]);
        $invoice = $this->makeInvoice([
            'auth' => 'login',
            'login_url' => self::LOGIN_URL,
            'username' => 'me',
            'password' => 'pw',
            'token_field' => 'jwt',
        ]);
        Position::factory()->for($invoice)->count(2)->create();

        ExternalApiService::pushInvoice($invoice);

        Http::assertSentCount(3);
        Http::assertSent(fn(Request $request) => $request->url() === self::LOGIN_URL
            && $request['username'] === 'me'
            && $request['password'] === 'pw');
        Http::assertSent(fn(Request $request) => $request->url() === self::URL
            && $request->hasHeader('Authorization', 'Bearer fresh-token'));
    }

    #[Test]
    public function it_reports_a_failed_login_without_pushing_any_position(): void
    {
        Http::fake([self::LOGIN_URL => Http::response(['error' => 'nope'], 401)]);
        $invoice = $this->makeInvoice(['auth' => 'login', 'login_url' => self::LOGIN_URL, 'username' => 'me', 'password' => 'pw']);
        Position::factory()->for($invoice)->create();

        $result = ExternalApiService::pushInvoice($invoice);

        $this->assertSame(0, $result['sent']);
        $this->assertCount(1, $result['errors']);
        Http::assertSentCount(1);
    }

    #[Test]
    public function it_continues_with_remaining_positions_after_a_failure(): void
    {
        Http::fake([self::URL => Http::sequence()->push(['message' => 'No budget left'], 422)->push(['id' => 5], 201)]);
        $invoice = $this->makeInvoice();
        $failing = Position::factory()->for($invoice)->create(['started_at' => '2026-03-01 09:00', 'finished_at' => '2026-03-01 10:00']);
        $working = Position::factory()->for($invoice)->create(['started_at' => '2026-03-02 09:00', 'finished_at' => '2026-03-02 10:00']);

        $result = ExternalApiService::pushInvoice($invoice);

        $this->assertSame(1, $result['sent']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('No budget left', $result['errors'][0]);
        $this->assertNull($failing->fresh()->external_id);
        $this->assertSame('5', $working->fresh()->external_id);
    }

    #[Test]
    public function it_reports_a_response_without_id_as_failure(): void
    {
        Http::fake([self::URL => Http::response(['ok' => true])]);
        $invoice = $this->makeInvoice();
        $position = Position::factory()->for($invoice)->create();

        $result = ExternalApiService::pushInvoice($invoice);

        $this->assertSame(0, $result['sent']);
        $this->assertCount(1, $result['errors']);
        $this->assertNull($position->fresh()->external_id);
    }

    /**
     * Create an invoice whose client has an api config, merged with the given overrides
     */
    private function makeInvoice(array $config = [], array $attributes = []): Invoice
    {
        $client = Client::factory()->create(['api_config' => [
            'url' => self::URL,
            'method' => 'POST',
            'auth' => 'none',
            'body' => self::BODY,
            'id_field' => 'id',
            ...$config,
        ]]);
        $project = Project::factory()->for($client)->create(['api_reference' => '42']);

        return Invoice::factory()->for($project)->create($attributes);
    }
}
