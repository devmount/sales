<?php

namespace Tests\Feature;

use App\Filament\Relations\PositionsRelationManager;
use App\Filament\Resources\InvoiceResource\Pages\EditInvoice;
use App\Models\Invoice;
use App\Models\Position;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;
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
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('positions', [
            'invoice_id' => $invoice->getKey(),
            'description' => 'Worked on feature X',
        ]);
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
            ->assertHasNoFormErrors();

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
            ->callAction(TestAction::make(DeleteAction::class)->table($position));

        $this->assertModelMissing($position);
    }
}
