<?php

namespace Tests\Feature;

use App\Enums\PricingUnit;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\InvoiceResource\Pages\EditInvoice;
use App\Filament\Resources\InvoiceResource\Pages\ListInvoices;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceResourceTest extends TestCase
{
    use RefreshDatabase;

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
}
