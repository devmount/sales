<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImportInvoiceDocumentsTest extends TestCase
{
    use RefreshDatabase;

    private string $directory;

    #[Test]
    public function it_imports_a_single_matching_invoice_pdf(): void
    {
        $invoice = Invoice::factory()->create(['invoiced_at' => '2026-03-11']);
        $this->putFixture($this->filename($invoice, '2026-03-11'));

        $this->artisan('documents:import-invoices', ['directory' => $this->directory])
            ->assertExitCode(0);

        $document = $invoice->documents()->sole();
        $this->assertSame($this->filename($invoice, '2026-03-11'), $document->filename);
        $this->assertSame('application/pdf', $document->mime_type);
        Storage::assertExists($document->path);
    }

    #[Test]
    public function it_reports_a_file_with_no_matching_invoice_as_unmatched(): void
    {
        $this->putFixture('202603119999_rechnung_acmeug.pdf');

        $this->artisan('documents:import-invoices', ['directory' => $this->directory])
            ->expectsOutputToContain('no invoice with id 9999')
            ->assertExitCode(0);

        $this->assertSame(0, Document::count());
    }

    #[Test]
    public function it_reports_an_unparseable_filename_as_unrecognized(): void
    {
        $this->putFixture('not-a-valid-invoice-filename.pdf');

        $this->artisan('documents:import-invoices', ['directory' => $this->directory])
            ->expectsOutputToContain('unrecognized filename')
            ->assertExitCode(0);

        $this->assertSame(0, Document::count());
    }

    #[Test]
    public function it_skips_an_invoice_that_already_has_a_document_unless_forced(): void
    {
        $invoice = Invoice::factory()->create(['invoiced_at' => '2026-03-11']);
        $existing = Document::factory()->for($invoice, 'documentable')->create();
        $this->putFixture($this->filename($invoice, '2026-03-11'));

        $this->artisan('documents:import-invoices', ['directory' => $this->directory])
            ->assertExitCode(0);

        $this->assertSame(1, $invoice->documents()->count());
        $this->assertTrue($invoice->documents()->sole()->is($existing));

        $this->artisan('documents:import-invoices', ['directory' => $this->directory, '--force' => true])
            ->assertExitCode(0);

        $this->assertModelMissing($existing);
        $this->assertSame(1, $invoice->documents()->count());
        $this->assertSame($this->filename($invoice, '2026-03-11'), $invoice->documents()->sole()->filename);
    }

    #[Test]
    public function it_treats_two_plain_files_for_the_same_invoice_as_a_conflict(): void
    {
        $invoice = Invoice::factory()->create(['invoiced_at' => '2026-03-11']);
        $this->putFixture($this->filename($invoice, '2026-03-11'));
        $this->putFixture($this->filename($invoice, '2026-03-12'));

        $this->artisan('documents:import-invoices', ['directory' => $this->directory])
            ->expectsOutputToContain('conflict')
            ->assertExitCode(0);

        $this->assertSame(0, $invoice->documents()->count());
    }

    #[Test]
    public function it_leaves_storage_and_database_untouched_on_a_dry_run(): void
    {
        $invoice = Invoice::factory()->create(['invoiced_at' => '2026-03-11']);
        $this->putFixture($this->filename($invoice, '2026-03-11'));

        $this->artisan('documents:import-invoices', ['directory' => $this->directory, '--dry-run' => true])
            ->expectsOutputToContain('dry run, nothing written')
            ->assertExitCode(0);

        $this->assertSame(0, $invoice->documents()->count());
        $this->assertSame(0, Document::count());
    }

    #[Test]
    public function it_ignores_non_pdf_files(): void
    {
        $invoice = Invoice::factory()->create(['invoiced_at' => '2026-03-11']);
        file_put_contents("{$this->directory}/" . str_replace('.pdf', '.txt', $this->filename($invoice, '2026-03-11')), 'not a pdf');

        $this->artisan('documents:import-invoices', ['directory' => $this->directory])
            ->assertExitCode(0);

        $this->assertSame(0, Document::count());
    }

    #[Test]
    public function it_imports_a_plain_and_version_2_pair_with_version_2_as_latest(): void
    {
        $invoice = Invoice::factory()->create(['invoiced_at' => '2026-03-11']);
        $plainName = $this->filename($invoice, '2026-03-11');
        $version2Name = str_replace('_rechnung', '-2_rechnung', $plainName);
        $this->putFixture($plainName);
        $this->putFixture($version2Name);

        $this->artisan('documents:import-invoices', ['directory' => $this->directory])
            ->assertExitCode(0);

        $this->assertSame(2, $invoice->documents()->count());
        $latest = $invoice->documents()->latest()->first();
        $this->assertSame($version2Name, $latest->filename);
    }

    #[Test]
    public function it_imports_a_lone_version_2_file_as_the_only_document(): void
    {
        $invoice = Invoice::factory()->create(['invoiced_at' => '2026-03-11']);
        $version2Name = str_replace('_rechnung', '-2_rechnung', $this->filename($invoice, '2026-03-11'));
        $this->putFixture($version2Name);

        $this->artisan('documents:import-invoices', ['directory' => $this->directory])
            ->assertExitCode(0);

        $document = $invoice->documents()->sole();
        $this->assertSame($version2Name, $document->filename);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();

        $this->directory = sys_get_temp_dir() . '/invoice-import-test-' . uniqid();
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        foreach (glob("{$this->directory}/*") as $file) {
            unlink($file);
        }
        rmdir($this->directory);

        parent::tearDown();
    }

    private function filename(Invoice $invoice, string $date): string
    {
        return str_replace('-', '', $date) . str_pad((string) $invoice->id, 4, '0', STR_PAD_LEFT) . '_rechnung_acmeug.pdf';
    }

    private function putFixture(string $filename): void
    {
        file_put_contents("{$this->directory}/{$filename}", '%PDF-1.4 fixture content');
    }
}
