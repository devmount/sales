<?php

namespace Tests\Feature;

use App\Enums\LanguageCode;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Position;
use App\Models\Project;
use App\Models\Setting;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    private string $logoPath;
    private string $signaturePath;

    #[Test]
    public function it_generates_and_saves_an_invoice_pdf_with_an_xml_attachment(): void
    {
        $invoice = $this->makeInvoice();
        Position::factory()->for($invoice)->create(['pause_duration' => 0]);

        $filename = InvoiceService::generatePdf($invoice);

        $this->assertSame($this->expectedPdfFilename($invoice), $filename);
        Storage::assertExists($filename);
        $this->assertStringStartsWith('%PDF-', Storage::get($filename));
        Storage::assertExists($this->expectedXmlFilename($invoice));
    }

    #[Test]
    public function it_generates_a_pdf_for_a_project_billed_invoice(): void
    {
        $client = Client::factory()->create(['language' => LanguageCode::DE]);
        $project = Project::factory()->for($client)->project()->create();
        $invoice = Invoice::factory()->for($project)->project()->create([
            'taxable' => true,
            'vat_rate' => 0.19,
            'discount' => null,
            'undated' => false,
        ]);
        Position::factory()->for($invoice)->create(['pause_duration' => 0]);

        $filename = InvoiceService::generatePdf($invoice);

        Storage::assertExists($filename);
        $this->assertStringStartsWith('%PDF-', Storage::get($filename));
    }

    #[Test]
    public function it_generates_a_pdf_with_a_discount(): void
    {
        $invoice = $this->makeInvoice(['discount' => 20.0]);
        Position::factory()->for($invoice)->create(['pause_duration' => 0]);

        $filename = InvoiceService::generatePdf($invoice);

        Storage::assertExists($filename);
        $this->assertStringStartsWith('%PDF-', Storage::get($filename));
    }

    #[Test]
    public function it_generates_a_pdf_for_a_non_taxable_invoice(): void
    {
        $invoice = $this->makeInvoice(['taxable' => false, 'vat_rate' => null]);
        Position::factory()->for($invoice)->create(['pause_duration' => 0]);

        $filename = InvoiceService::generatePdf($invoice);

        Storage::assertExists($filename);
        $this->assertStringStartsWith('%PDF-', Storage::get($filename));
    }

    #[Test]
    public function it_generates_a_pdf_in_the_clients_language(): void
    {
        $invoice = $this->makeInvoice(['language' => LanguageCode::EN]);
        Position::factory()->for($invoice)->create(['pause_duration' => 0]);

        $filename = InvoiceService::generatePdf($invoice);

        $this->assertSame($this->expectedPdfFilename($invoice, 'en'), $filename);
        Storage::assertExists($filename);
    }

    #[Test]
    public function it_generates_a_pdf_with_multiple_positions(): void
    {
        $invoice = $this->makeInvoice();
        Position::factory()->for($invoice)->count(3)->create([
            'pause_duration' => 0,
            'description' => "Line one\nLine two\nLine three",
        ]);

        $filename = InvoiceService::generatePdf($invoice);

        Storage::assertExists($filename);
        $this->assertStringStartsWith('%PDF-', Storage::get($filename));
    }

    #[Test]
    public function it_generates_an_en16931_xml_invoice_with_the_expected_content(): void
    {
        $invoice = $this->makeInvoice();
        Position::factory()->for($invoice)->create(['pause_duration' => 0, 'description' => 'Some work']);

        $filename = InvoiceService::generateEn16931Xml($invoice);

        $this->assertSame($this->expectedXmlFilename($invoice), $filename);
        Storage::assertExists($filename);

        $xml = Storage::get($filename);
        $this->assertStringContainsString('<cbc:ID>' . $invoice->current_number . '</cbc:ID>', $xml);
        $this->assertStringContainsString('<cbc:RegistrationName>Acme UG</cbc:RegistrationName>', $xml);
        $this->assertStringContainsString('<cbc:RegistrationName>' . $invoice->project->client->name . '</cbc:RegistrationName>', $xml);
        $this->assertStringContainsString('<cbc:PayableAmount currencyID="EUR">' . $invoice->gross . '</cbc:PayableAmount>', $xml);
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

    private function makeInvoice(array $attributes = []): Invoice
    {
        $client = Client::factory()->create([
            'language' => $attributes['language'] ?? LanguageCode::DE,
            'name' => 'Client GmbH',
        ]);
        unset($attributes['language']);
        $project = Project::factory()->for($client)->hourly()->create();

        return Invoice::factory()->for($project)->create(array_merge([
            'taxable' => true,
            'vat_rate' => 0.19,
            'discount' => null,
            'undated' => false,
        ], $attributes));
    }

    private function expectedPdfFilename(Invoice $invoice, string $locale = 'de'): string
    {
        return strtolower("{$invoice->current_number}_" . trans_choice('invoice', 1, [], $locale) . '_Acme UG.pdf');
    }

    private function expectedXmlFilename(Invoice $invoice, string $locale = 'de'): string
    {
        return strtolower("{$invoice->current_number}_" . trans_choice('invoice', 1, [], $locale) . '_Acme UG.xml');
    }
}
