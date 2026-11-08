<?php

namespace Tests\Feature;

use App\Filament\Relations\PositionsRelationManager;
use App\Filament\Resources\InvoiceResource\Pages\EditInvoice;
use App\Models\Invoice;
use App\Models\Position;
use App\Models\Project;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\Testing\TestAction;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PositionsRelationManagerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renders_for_an_invoice(): void
    {
        $this->actingAs(User::factory()->create());
        $invoice = Invoice::factory()->create();

        Livewire::test(PositionsRelationManager::class, [
            'ownerRecord' => $invoice,
            'pageClass' => EditInvoice::class,
        ])->assertSuccessful();
    }

    #[Test]
    public function it_lists_the_invoices_positions(): void
    {
        $this->actingAs(User::factory()->create());
        $invoice = Invoice::factory()->create();
        $positions = Position::factory()->count(3)->for($invoice)->create();
        $otherInvoicesPosition = Position::factory()->create();

        Livewire::test(PositionsRelationManager::class, [
            'ownerRecord' => $invoice,
            'pageClass' => EditInvoice::class,
        ])
            ->assertCanSeeTableRecords($positions)
            ->assertCanNotSeeTableRecords([$otherInvoicesPosition]);
    }

    #[Test]
    public function it_prefills_the_invoice_when_creating_a_position(): void
    {
        $this->actingAs(User::factory()->create());
        $invoice = Invoice::factory()->create();

        Livewire::test(PositionsRelationManager::class, [
            'ownerRecord' => $invoice,
            'pageClass' => EditInvoice::class,
        ])
            ->mountAction(TestAction::make(CreateAction::class)->table(true))
            ->assertActionDataSet([
                'invoice_id' => $invoice->getKey(),
                'pause_duration' => 0,
            ]);
    }

    #[Test]
    public function it_creates_a_position_for_the_invoice(): void
    {
        $this->actingAs(User::factory()->create());
        $invoice = Invoice::factory()->create();

        Livewire::test(PositionsRelationManager::class, [
            'ownerRecord' => $invoice,
            'pageClass' => EditInvoice::class,
        ])
            ->callAction(TestAction::make(CreateAction::class)->table(true), data: [
                'invoice_id' => $invoice->getKey(),
                'started_at' => '2026-03-15 09:00:00',
                'finished_at' => '2026-03-15 17:00:00',
                'description' => 'Worked on feature X',
            ])
            ->assertHasNoFormErrors()
            ->assertDispatched('refresh-page');

        $this->assertDatabaseHas('positions', [
            'invoice_id' => $invoice->getKey(),
            'description' => 'Worked on feature X',
        ]);
    }

    #[Test]
    public function it_shows_recent_descriptions_of_the_same_client_when_creating_a_position(): void
    {
        $this->actingAs(User::factory()->create());
        $invoice = Invoice::factory()->create();
        $sameClientInvoice = Invoice::factory()->for(Project::factory()->for($invoice->project->client))->create();
        Position::factory()->for($sameClientInvoice)->create(['description' => 'Same client work']);
        Position::factory()->create(['description' => 'Other client work']);

        Livewire::test(PositionsRelationManager::class, [
            'ownerRecord' => $invoice,
            'pageClass' => EditInvoice::class,
        ])
            ->mountAction(TestAction::make(CreateAction::class)->table(true))
            ->assertSchemaComponentExists(
                'history_0',
                checkComponentUsing: fn(TextEntry $entry): bool => $entry->getState() === 'Same client work',
            )
            ->assertSchemaComponentDoesNotExist('history_1');
    }

    #[Test]
    public function it_fills_the_description_from_history(): void
    {
        $this->actingAs(User::factory()->create());
        $invoice = Invoice::factory()->create();
        Position::factory()->for($invoice)->create(['description' => 'Reused description']);

        Livewire::test(PositionsRelationManager::class, [
            'ownerRecord' => $invoice,
            'pageClass' => EditInvoice::class,
        ])
            ->mountAction(TestAction::make(CreateAction::class)->table(true))
            ->callAction(TestAction::make('useDescription')->schemaComponent('history_0'))
            ->assertActionDataSet(['description' => 'Reused description']);
    }

    #[Test]
    public function it_requires_start_finish_and_description_when_creating_a_position(): void
    {
        $this->actingAs(User::factory()->create());
        $invoice = Invoice::factory()->create();

        Livewire::test(PositionsRelationManager::class, [
            'ownerRecord' => $invoice,
            'pageClass' => EditInvoice::class,
        ])
            ->callAction(TestAction::make(CreateAction::class)->table(true), data: [
                'invoice_id' => $invoice->getKey(),
                'started_at' => '',
                'finished_at' => '',
                'description' => '',
            ])
            ->assertHasFormErrors(['started_at' => 'required', 'finished_at' => 'required', 'description' => 'required']);

        $this->assertDatabaseCount('positions', 0);
    }

    #[Test]
    public function it_updates_a_position(): void
    {
        $this->actingAs(User::factory()->create());
        $invoice = Invoice::factory()->create();
        $position = Position::factory()->for($invoice)->create(['description' => 'Old description']);

        Livewire::test(PositionsRelationManager::class, [
            'ownerRecord' => $invoice,
            'pageClass' => EditInvoice::class,
        ])
            ->callAction(TestAction::make(EditAction::class)->table($position), data: ['description' => 'New description'])
            ->assertHasNoFormErrors()
            ->assertDispatched('refresh-page');

        $this->assertSame('New description', $position->refresh()->description);
    }

    #[Test]
    public function it_requires_a_description_when_updating_a_position(): void
    {
        $this->actingAs(User::factory()->create());
        $invoice = Invoice::factory()->create();
        $position = Position::factory()->for($invoice)->create();

        Livewire::test(PositionsRelationManager::class, [
            'ownerRecord' => $invoice,
            'pageClass' => EditInvoice::class,
        ])
            ->callAction(TestAction::make(EditAction::class)->table($position), data: ['description' => ''])
            ->assertHasFormErrors(['description' => 'required']);
    }

    #[Test]
    public function it_deletes_a_position(): void
    {
        $this->actingAs(User::factory()->create());
        $invoice = Invoice::factory()->create();
        $position = Position::factory()->for($invoice)->create();

        Livewire::test(PositionsRelationManager::class, [
            'ownerRecord' => $invoice,
            'pageClass' => EditInvoice::class,
        ])
            ->callAction(TestAction::make(DeleteAction::class)->table($position))
            ->assertDispatched('refresh-page');

        $this->assertModelMissing($position);
    }

    #[Test]
    public function it_replicates_a_position(): void
    {
        $this->actingAs(User::factory()->create());
        $invoice = Invoice::factory()->create();
        $position = Position::factory()->for($invoice)->create();

        Livewire::test(PositionsRelationManager::class, [
            'ownerRecord' => $invoice,
            'pageClass' => EditInvoice::class,
        ])
            ->callAction(TestAction::make(ReplicateAction::class)->table($position))
            ->assertHasNoFormErrors()
            ->assertDispatched('refresh-page');

        $this->assertDatabaseCount('positions', 2);
    }

    #[Test]
    public function it_bulk_deletes_positions(): void
    {
        $this->actingAs(User::factory()->create());
        $invoice = Invoice::factory()->create();
        $positions = Position::factory()->count(2)->for($invoice)->create();

        Livewire::test(PositionsRelationManager::class, [
            'ownerRecord' => $invoice,
            'pageClass' => EditInvoice::class,
        ])
            ->selectTableRecords($positions)
            ->callAction(TestAction::make(DeleteBulkAction::class)->table()->bulk())
            ->assertDispatched('refresh-page');

        $this->assertDatabaseCount('positions', 0);
    }
}
