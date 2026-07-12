<?php

namespace Tests\Feature;

use App\Enums\ExpenseCategory;
use App\Models\Client;
use App\Models\Estimate;
use App\Models\Expense;
use App\Models\Gift;
use App\Models\Invoice;
use App\Models\Position;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AnonymizePersonalDataTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_anonymizes_client_personal_data(): void
    {
        $client = Client::factory()->create([
            'name' => 'Real Client GmbH',
            'email' => 'real@client.test',
            'phone' => '+491234567890',
            'country' => 'Germany',
        ]);

        $this->artisan('db:anonymize')->assertSuccessful();

        $client->refresh();
        $this->assertNotSame('Real Client GmbH', $client->name);
        $this->assertNotSame('real@client.test', $client->email);
        $this->assertNotSame('+491234567890', $client->phone);
        $this->assertSame('DE', $client->country);
    }

    #[Test]
    public function it_anonymizes_project_estimate_and_invoice_titles_and_descriptions(): void
    {
        $project = Project::factory()->create(['title' => 'Real Project Title', 'description' => 'Real project description']);
        $estimate = Estimate::factory()->for($project)->create(['title' => 'Real Estimate Title', 'description' => 'Real estimate description']);
        $invoice = Invoice::factory()->for($project)->create(['title' => 'Real Invoice Title', 'description' => 'Real invoice description']);

        $this->artisan('db:anonymize')->assertSuccessful();

        $this->assertNotSame('Real Project Title', $project->refresh()->title);
        $this->assertNotSame('Real project description', $project->description);
        $this->assertNotSame('Real Estimate Title', $estimate->refresh()->title);
        $this->assertNotSame('Real estimate description', $estimate->description);
        $this->assertNotSame('Real Invoice Title', $invoice->refresh()->title);
        $this->assertNotSame('Real invoice description', $invoice->description);
    }

    #[Test]
    public function it_anonymizes_position_descriptions_and_normalizes_the_pause_duration(): void
    {
        $invoice = Invoice::factory()->create();
        $position = Position::factory()->for($invoice)->create([
            'description' => 'Real position description',
            'pause_duration' => 1.5,
        ]);

        $this->artisan('db:anonymize')->assertSuccessful();

        $position->refresh();
        $this->assertNotSame('Real position description', $position->description);
        $this->assertSame(0.0, $position->pause_duration);
    }

    #[Test]
    public function it_anonymizes_descriptions_of_non_tax_expenses_but_leaves_tax_expenses_untouched(): void
    {
        $goodExpense = Expense::factory()->create(['category' => ExpenseCategory::Good, 'description' => 'Real good description']);
        $vatExpense = Expense::factory()->create(['category' => ExpenseCategory::Vat, 'description' => 'Real vat description']);
        $taxExpense = Expense::factory()->create(['category' => ExpenseCategory::Tax, 'description' => 'Real tax description']);

        $this->artisan('db:anonymize')->assertSuccessful();

        $this->assertNotSame('Real good description', $goodExpense->refresh()->description);
        $this->assertSame('Real vat description', $vatExpense->refresh()->description);
        $this->assertSame('Real tax description', $taxExpense->refresh()->description);
    }

    #[Test]
    public function it_anonymizes_gift_personal_data(): void
    {
        $gift = Gift::factory()->create([
            'subject' => 'Real gift subject',
            'name' => 'Real Name',
            'email' => 'real@gift.test',
        ]);

        $this->artisan('db:anonymize')->assertSuccessful();

        $gift->refresh();
        $this->assertNotSame('Real gift subject', $gift->subject);
        $this->assertNotSame('Real Name', $gift->name);
        $this->assertNotSame('real@gift.test', $gift->email);
    }

    #[Test]
    public function it_refuses_to_run_in_the_production_environment(): void
    {
        config(['app.env' => 'production']);

        $client = Client::factory()->create(['name' => 'Real Client GmbH']);

        $this->expectException(HttpException::class);

        try {
            $this->artisan('db:anonymize');
        } finally {
            $this->assertSame('Real Client GmbH', $client->refresh()->name);
        }
    }
}
