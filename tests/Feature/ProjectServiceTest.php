<?php

namespace Tests\Feature;

use App\Enums\LanguageCode;
use App\Models\Client;
use App\Models\Estimate;
use App\Models\Project;
use App\Models\Setting;
use App\Services\ProjectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProjectServiceTest extends TestCase
{
    use RefreshDatabase;

    private string $logoPath;
    private string $signaturePath;

    #[Test]
    public function it_generates_and_saves_a_quote_pdf_for_an_hourly_project(): void
    {
        $client = Client::factory()->create(['language' => LanguageCode::DE]);
        $project = Project::factory()->for($client)->hourly()->create();
        Estimate::factory()->for($project)->create();

        $filename = ProjectService::generateQuotePdf($project);

        $this->assertSame($this->expectedFilename(), $filename);
        Storage::assertExists($filename);
        $this->assertStringStartsWith('%PDF-', Storage::get($filename));
    }

    #[Test]
    public function it_generates_a_quote_pdf_for_a_project_billed_at_a_flat_price(): void
    {
        $client = Client::factory()->create(['language' => LanguageCode::DE]);
        $project = Project::factory()->for($client)->project()->create(['scope' => 10]);
        Estimate::factory()->for($project)->create();

        $filename = ProjectService::generateQuotePdf($project);

        Storage::assertExists($filename);
        $this->assertStringStartsWith('%PDF-', Storage::get($filename));
    }

    #[Test]
    public function it_generates_a_quote_pdf_in_the_clients_language(): void
    {
        $client = Client::factory()->create(['language' => LanguageCode::EN]);
        $project = Project::factory()->for($client)->hourly()->create();
        Estimate::factory()->for($project)->create();

        $filename = ProjectService::generateQuotePdf($project);

        $this->assertSame(
            strtolower(now()->format('Ymd') . '_' . __('quote', locale: 'en') . '_Acme UG.pdf'),
            $filename,
        );
        Storage::assertExists($filename);
    }

    #[Test]
    public function it_generates_a_quote_pdf_with_multiple_estimates(): void
    {
        $client = Client::factory()->create(['language' => LanguageCode::DE]);
        $project = Project::factory()->for($client)->hourly()->create();
        Estimate::factory()->for($project)->count(3)->create([
            'description' => "Line one\nLine two\nLine three",
        ]);

        $filename = ProjectService::generateQuotePdf($project);

        Storage::assertExists($filename);
        $this->assertStringStartsWith('%PDF-', Storage::get($filename));
    }

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();

        $this->logoPath = tempnam(sys_get_temp_dir(), 'logo') . '.jpg';
        imagejpeg(imagecreatetruecolor(10, 10), $this->logoPath);

        $this->signaturePath = tempnam(sys_get_temp_dir(), 'signature') . '.png';
        imagepng(imagecreatetruecolor(10, 10), $this->signaturePath);

        $this->seedSettings();
    }

    protected function tearDown(): void
    {
        @unlink($this->logoPath);
        @unlink($this->signaturePath);

        parent::tearDown();
    }

    private function seedSettings(): void
    {
        $values = [
            'accountHolder' => 'Account Holder',
            'bank' => 'Test Bank',
            'bic' => 'TESTBIC1',
            'city' => 'Berlin',
            'company' => 'Acme UG',
            'country' => 'Germany',
            'email' => 'contact@acme.test',
            'iban' => 'DE00000000000000000000',
            'logo' => $this->logoPath,
            'name' => 'Acme UG',
            'phone' => '+49123456789',
            'signature' => $this->signaturePath,
            'street' => 'Main Street 1',
            'taxOffice' => 'Finanzamt Berlin',
            'vatId' => 'DE123456789',
            'vatRate' => '0.19',
            'website' => 'https://acme.test',
            'zip' => '12345',
        ];

        foreach ($values as $field => $value) {
            Setting::where('field', $field)->update(['value' => $value]);
        }
    }

    private function expectedFilename(): string
    {
        return strtolower(now()->format('Ymd') . '_' . __('quote', locale: 'de') . '_Acme UG.pdf');
    }
}
