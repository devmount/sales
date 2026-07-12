<?php

namespace Tests\Feature;

use App\Enums\PricingUnit;
use App\Filament\Relations\InvoicesRelationManager;
use App\Filament\Resources\ClientResource\Pages\EditClient;
use App\Filament\Resources\ProjectResource\Pages\EditProject;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoicesRelationManagerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renders_for_a_project(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();

        Livewire::test(InvoicesRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => EditProject::class,
        ])->assertSuccessful();
    }

    #[Test]
    public function it_renders_for_a_client(): void
    {
        $this->actingAs(User::factory()->create());
        $client = Client::factory()->create();

        Livewire::test(InvoicesRelationManager::class, [
            'ownerRecord' => $client,
            'pageClass' => EditClient::class,
        ])->assertSuccessful();
    }

    #[Test]
    public function it_lists_the_projects_invoices(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();
        $invoices = Invoice::factory()->count(3)->for($project)->create();
        $otherProjectsInvoice = Invoice::factory()->create();

        Livewire::test(InvoicesRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => EditProject::class,
        ])
            ->loadTable()
            ->assertCanSeeTableRecords($invoices)
            ->assertCanNotSeeTableRecords([$otherProjectsInvoice]);
    }

    #[Test]
    public function it_lists_the_clients_invoices_across_its_projects(): void
    {
        $this->actingAs(User::factory()->create());
        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create();
        $invoice = Invoice::factory()->for($project)->create();
        $otherClientsInvoice = Invoice::factory()->create();

        Livewire::test(InvoicesRelationManager::class, [
            'ownerRecord' => $client,
            'pageClass' => EditClient::class,
        ])
            ->loadTable()
            ->assertCanSeeTableRecords([$invoice])
            ->assertCanNotSeeTableRecords([$otherClientsInvoice]);
    }

    #[Test]
    public function it_offers_creating_an_invoice_under_a_project(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();

        Livewire::test(InvoicesRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => EditProject::class,
        ])->assertActionVisible(TestAction::make(CreateAction::class)->table(true));
    }

    #[Test]
    public function it_does_not_offer_creating_an_invoice_under_a_client(): void
    {
        $this->actingAs(User::factory()->create());
        $client = Client::factory()->create();

        Livewire::test(InvoicesRelationManager::class, [
            'ownerRecord' => $client,
            'pageClass' => EditClient::class,
        ])->assertActionHidden(TestAction::make(CreateAction::class)->table(true));
    }

    #[Test]
    public function it_prefills_the_project_when_creating_an_invoice_under_a_project(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();

        Livewire::test(InvoicesRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => EditProject::class,
        ])
            ->mountAction(TestAction::make(CreateAction::class)->table(true))
            ->assertActionDataSet([
                'project_id' => $project->getKey(),
                'taxable' => true,
                'vat_rate' => 0.19,
            ]);
    }

    #[Test]
    public function it_creates_an_invoice_for_the_project(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();

        Livewire::test(InvoicesRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => EditProject::class,
        ])
            ->callAction(TestAction::make(CreateAction::class)->table(true), data: [
                'project_id' => $project->getKey(),
                'title' => 'New invoice',
                'price' => 500,
                'pricing_unit' => PricingUnit::Project->value,
            ])
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('invoices', [
            'project_id' => $project->getKey(),
            'title' => 'New invoice',
        ]);
    }

    #[Test]
    public function it_requires_a_title_price_and_pricing_unit_when_creating_an_invoice(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();

        Livewire::test(InvoicesRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => EditProject::class,
        ])
            ->callAction(TestAction::make(CreateAction::class)->table(true), data: [
                'project_id' => $project->getKey(),
                'title' => '',
                'price' => '',
                'pricing_unit' => '',
            ])
            ->assertHasFormErrors(['title' => 'required', 'price' => 'required', 'pricing_unit' => 'required']);

        $this->assertDatabaseCount('invoices', 0);
    }

    #[Test]
    public function it_updates_an_invoice(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();
        $invoice = Invoice::factory()->for($project)->create(['title' => 'Old title']);

        Livewire::test(InvoicesRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => EditProject::class,
        ])
            ->loadTable()
            ->callAction(TestAction::make(EditAction::class)->table($invoice), data: ['title' => 'New title'])
            ->assertHasNoFormErrors();

        $this->assertSame('New title', $invoice->refresh()->title);
    }

    #[Test]
    public function it_requires_a_title_when_updating_an_invoice(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();
        $invoice = Invoice::factory()->for($project)->create();

        Livewire::test(InvoicesRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => EditProject::class,
        ])
            ->loadTable()
            ->callAction(TestAction::make(EditAction::class)->table($invoice), data: ['title' => ''])
            ->assertHasFormErrors(['title' => 'required']);
    }

    #[Test]
    public function it_deletes_an_invoice_via_bulk_action(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();
        $invoice = Invoice::factory()->for($project)->create();

        Livewire::test(InvoicesRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => EditProject::class,
        ])
            ->loadTable()
            ->callTableBulkAction(DeleteBulkAction::class, [$invoice]);

        $this->assertModelMissing($invoice);
    }
}
