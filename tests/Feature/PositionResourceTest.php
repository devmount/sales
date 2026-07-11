<?php

namespace Tests\Feature;

use App\Filament\Resources\PositionResource;
use App\Filament\Resources\PositionResource\Pages\ListPositions;
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

class PositionResourceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_redirects_guests_away_from_the_position_list(): void
    {
        $this->get(PositionResource::getUrl('index'))->assertRedirect();
    }

    #[Test]
    public function it_renders_the_position_list_page_for_authenticated_users(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ListPositions::class)->assertSuccessful();
    }

    #[Test]
    public function it_lists_positions_in_the_table(): void
    {
        $this->actingAs(User::factory()->create());
        $positions = Position::factory()->count(3)->create();

        Livewire::test(ListPositions::class)
            ->loadTable()
            ->assertCanSeeTableRecords($positions);
    }

    #[Test]
    public function it_creates_a_position(): void
    {
        $this->actingAs(User::factory()->create());
        $invoice = Invoice::factory()->create();

        $data = [
            'invoice_id' => $invoice->getKey(),
            'started_at' => '2026-03-15 09:00:00',
            'finished_at' => '2026-03-15 17:00:00',
            'pause_duration' => 0.5,
            'remote' => true,
            'description' => 'Worked on feature X',
        ];

        Livewire::test(ListPositions::class)
            ->callAction(CreateAction::class, data: $data)
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('positions', [
            'invoice_id' => $invoice->getKey(),
            'description' => 'Worked on feature X',
        ]);
    }

    #[Test]
    public function it_requires_an_invoice_start_finish_and_description_when_creating_a_position(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ListPositions::class)
            ->callAction(CreateAction::class, data: [
                'invoice_id' => '',
                'started_at' => '',
                'finished_at' => '',
                'description' => '',
            ])
            ->assertHasFormErrors([
                'invoice_id' => 'required',
                'started_at' => 'required',
                'finished_at' => 'required',
                'description' => 'required',
            ]);

        $this->assertDatabaseCount('positions', 0);
    }

    #[Test]
    public function it_updates_a_position(): void
    {
        $this->actingAs(User::factory()->create());
        $position = Position::factory()->create(['description' => 'Old description']);

        Livewire::test(ListPositions::class)
            ->callAction(TestAction::make(EditAction::class)->table($position), data: ['description' => 'New description'])
            ->assertHasNoFormErrors();

        $this->assertSame('New description', $position->refresh()->description);
    }

    #[Test]
    public function it_requires_a_description_when_updating_a_position(): void
    {
        $this->actingAs(User::factory()->create());
        $position = Position::factory()->create();

        Livewire::test(ListPositions::class)
            ->callAction(TestAction::make(EditAction::class)->table($position), data: ['description' => ''])
            ->assertHasFormErrors(['description' => 'required']);
    }

    #[Test]
    public function it_deletes_a_position_from_the_table(): void
    {
        $this->actingAs(User::factory()->create());
        $position = Position::factory()->create();

        Livewire::test(ListPositions::class)
            ->callAction(TestAction::make(DeleteAction::class)->table($position));

        $this->assertModelMissing($position);
    }
}
