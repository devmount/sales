<?php

namespace Tests\Feature;

use App\Enums\PricingUnit;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\InvoiceResource\Pages\EditInvoice;
use App\Filament\Resources\InvoiceResource\Pages\ListInvoices;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Setting;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceResourceTest extends TestCase
{
    use RefreshDatabase;

    private string $logoPath;
    private string $signaturePath;

    #[Test]
    public function it_redirects_guests_away_from_the_invoice_list(): void
    {
        $this->get(InvoiceResource::getUrl('index'))->assertRedirect();
    }

    #[Test]
    public function it_renders_the_invoice_list_page_for_authenticated_users(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ListInvoices::class)->assertSuccessful();
    }

    #[Test]
    public function it_lists_invoices_in_the_table(): void
    {
        $this->actingAs(User::factory()->create());
        $invoices = Invoice::factory()->count(3)->create();

        Livewire::test(ListInvoices::class, ['activeTab' => 'all'])
            ->loadTable()
            ->assertCanSeeTableRecords($invoices);
    }

    #[Test]
    public function it_creates_an_invoice(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();

        $data = [
            'project_id' => $project->getKey(),
            'title' => 'New invoice',
            'description' => 'Some work',
            'price' => 500,
            'pricing_unit' => PricingUnit::Project->value,
            'taxable' => true,
            'vat_rate' => 0.19,
        ];

        Livewire::test(ListInvoices::class)
            ->callAction(CreateAction::class, data: $data)
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('invoices', [
            'project_id' => $project->getKey(),
            'title' => 'New invoice',
            'price' => 500,
        ]);
    }

    #[Test]
    public function it_requires_a_project_title_price_and_pricing_unit_when_creating_an_invoice(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ListInvoices::class)
            ->callAction(CreateAction::class, data: [
                'project_id' => '',
                'title' => '',
                'price' => '',
                'pricing_unit' => '',
            ])
            ->assertHasFormErrors([
                'project_id' => 'required',
                'title' => 'required',
                'price' => 'required',
                'pricing_unit' => 'required',
            ]);

        $this->assertDatabaseCount('invoices', 0);
    }

    #[Test]
    public function it_updates_an_invoice(): void
    {
        $this->actingAs(User::factory()->create());
        $invoice = Invoice::factory()->create(['title' => 'Old title']);

        Livewire::test(EditInvoice::class, ['record' => $invoice->getKey()])
            ->fillForm(['title' => 'New title'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('New title', $invoice->refresh()->title);
    }

    #[Test]
    public function it_requires_a_title_when_updating_an_invoice(): void
    {
        $this->actingAs(User::factory()->create());
        $invoice = Invoice::factory()->create();

        Livewire::test(EditInvoice::class, ['record' => $invoice->getKey()])
            ->fillForm(['title' => ''])
            ->call('save')
            ->assertHasFormErrors(['title' => 'required']);
    }

    #[Test]
    public function it_deletes_an_invoice_from_the_edit_page(): void
    {
        $this->actingAs(User::factory()->create());
        $invoice = Invoice::factory()->create();

        Livewire::test(EditInvoice::class, ['record' => $invoice->getKey()])
            ->callAction(DeleteAction::class);

        $this->assertModelMissing($invoice);
    }

    #[Test]
    public function it_deletes_an_invoice_from_the_table(): void
    {
        $this->actingAs(User::factory()->create());
        $invoice = Invoice::factory()->create();

        Livewire::test(ListInvoices::class, ['activeTab' => 'all'])
            ->callAction(TestAction::make(DeleteAction::class)->table($invoice));

        $this->assertModelMissing($invoice);
    }

    #[Test]
    public function it_disables_pdf_and_xml_downloads_on_the_edit_page_until_matching_documents_exist(): void
    {
        $this->actingAs(User::factory()->create());
        $invoice = Invoice::factory()->create();

        Livewire::test(EditInvoice::class, ['record' => $invoice->getKey()])
            ->assertActionDisabled('pdf')
            ->assertActionDisabled('xml');

        Document::factory()->for($invoice, 'documentable')->create(['filename' => 'invoice.pdf', 'attachment_path' => null]);

        Livewire::test(EditInvoice::class, ['record' => $invoice->getKey()])
            ->assertActionEnabled('pdf')
            ->assertActionDisabled('xml');

        Document::factory()->for($invoice, 'documentable')->create([
            'filename' => 'invoice.pdf',
            'attachment_path' => 'documents/invoice.xml',
            'attachment_filename' => 'invoice.xml',
        ]);

        Livewire::test(EditInvoice::class, ['record' => $invoice->getKey()])
            ->assertActionEnabled('pdf')
            ->assertActionEnabled('xml');
    }

    #[Test]
    public function it_hides_pdf_and_xml_downloads_in_the_table_until_matching_documents_exist(): void
    {
        $this->actingAs(User::factory()->create());
        $invoice = Invoice::factory()->create();

        Livewire::test(ListInvoices::class, ['activeTab' => 'all'])
            ->assertTableActionHidden('pdf', $invoice)
            ->assertTableActionHidden('xml', $invoice);

        Document::factory()->for($invoice, 'documentable')->create([
            'filename' => 'invoice.pdf',
            'attachment_path' => 'documents/invoice.xml',
            'attachment_filename' => 'invoice.xml',
        ]);

        Livewire::test(ListInvoices::class, ['activeTab' => 'all'])
            ->assertTableActionVisible('pdf', $invoice)
            ->assertTableActionVisible('xml', $invoice);
    }

    #[Test]
    public function it_generates_and_attaches_a_single_invoice_document_from_the_edit_page(): void
    {
        $this->actingAs(User::factory()->create());
        $invoice = Invoice::factory()->create();

        Livewire::test(EditInvoice::class, ['record' => $invoice->getKey()])
            ->callAction('generate');

        $this->assertSame(1, $invoice->documents()->count());
        $this->assertDatabaseHas('documents', [
            'documentable_type' => Invoice::class,
            'documentable_id' => $invoice->id,
            'mime_type' => 'application/pdf',
            'attachment_mime_type' => 'application/xml',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();

        $this->logoPath = tempnam(sys_get_temp_dir(), 'logo') . '.jpg';
        imagejpeg(imagecreatetruecolor(10, 10), $this->logoPath);

        $this->signaturePath = tempnam(sys_get_temp_dir(), 'signature') . '.png';
        imagepng(imagecreatetruecolor(10, 10), $this->signaturePath);

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

    protected function tearDown(): void
    {
        @unlink($this->logoPath);
        @unlink($this->signaturePath);

        parent::tearDown();
    }
}
