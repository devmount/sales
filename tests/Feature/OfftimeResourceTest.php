<?php

namespace Tests\Feature;

use App\Enums\OfftimeCategory;
use App\Filament\Resources\OfftimeResource;
use App\Filament\Resources\OfftimeResource\Pages\ListOfftimes;
use App\Models\Offtime;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OfftimeResourceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_redirects_guests_away_from_the_offtime_list(): void
    {
        $this->get(OfftimeResource::getUrl('index'))->assertRedirect();
    }

    #[Test]
    public function it_renders_the_offtime_list_page_for_authenticated_users(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ListOfftimes::class)->assertSuccessful();
    }

    #[Test]
    public function it_lists_offtimes_in_the_table(): void
    {
        $this->actingAs(User::factory()->create());
        $offtimes = Offtime::factory()->count(3)->create();

        Livewire::test(ListOfftimes::class)
            ->loadTable()
            ->assertCanSeeTableRecords($offtimes);
    }

    #[Test]
    public function it_creates_an_offtime(): void
    {
        $this->actingAs(User::factory()->create());

        $data = [
            'start' => '2026-03-15',
            'end' => '2026-03-18',
            'category' => OfftimeCategory::Vacation->value,
            'description' => 'Spring break',
        ];

        Livewire::test(ListOfftimes::class)
            ->callAction(CreateAction::class, data: $data)
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('offtimes', [
            'category' => OfftimeCategory::Vacation->value,
            'description' => 'Spring break',
        ]);
    }

    #[Test]
    public function it_requires_a_start_date_and_category_when_creating_an_offtime(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ListOfftimes::class)
            ->callAction(CreateAction::class, data: [
                'start' => '',
                'category' => '',
            ])
            ->assertHasFormErrors(['start' => 'required', 'category' => 'required']);

        $this->assertDatabaseCount('offtimes', 0);
    }

    #[Test]
    public function it_requires_the_end_date_to_be_after_the_start_date(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ListOfftimes::class)
            ->callAction(CreateAction::class, data: [
                'start' => '2026-01-10',
                'end' => '2026-01-10',
                'category' => OfftimeCategory::Vacation,
            ])
            ->assertHasFormErrors(['end' => 'after']);

        $this->assertDatabaseCount('offtimes', 0);
    }

    #[Test]
    public function it_sets_the_end_date_to_the_day_after_a_changed_start_date(): void
    {
        $this->actingAs(User::factory()->create());
        $offtime = Offtime::factory()->create(['start' => '2026-01-01', 'end' => '2026-01-05']);

        Livewire::test(ListOfftimes::class)
            ->mountAction(TestAction::make(EditAction::class)->table($offtime))
            ->fillForm(['start' => '2026-01-10'])
            ->assertActionDataSet(['end' => '2026-01-11']);
    }

    #[Test]
    public function it_keeps_the_end_date_empty_when_changing_the_start_date(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ListOfftimes::class)
            ->mountAction(CreateAction::class)
            ->fillForm(['start' => '2026-01-10'])
            ->assertActionDataSet(['end' => null]);
    }

    #[Test]
    public function it_updates_an_offtime(): void
    {
        $this->actingAs(User::factory()->create());
        $offtime = Offtime::factory()->create(['description' => 'Old description']);

        Livewire::test(ListOfftimes::class)
            ->callAction(TestAction::make(EditAction::class)->table($offtime), data: ['description' => 'New description'])
            ->assertHasNoFormErrors();

        $this->assertSame('New description', $offtime->refresh()->description);
    }

    #[Test]
    public function it_requires_a_start_date_when_updating_an_offtime(): void
    {
        $this->actingAs(User::factory()->create());
        $offtime = Offtime::factory()->create();

        Livewire::test(ListOfftimes::class)
            ->callAction(TestAction::make(EditAction::class)->table($offtime), data: ['start' => ''])
            ->assertHasFormErrors(['start' => 'required']);
    }

    #[Test]
    public function it_deletes_an_offtime_from_the_table(): void
    {
        $this->actingAs(User::factory()->create());
        $offtime = Offtime::factory()->create();

        Livewire::test(ListOfftimes::class)
            ->callAction(TestAction::make(DeleteAction::class)->table($offtime));

        $this->assertModelMissing($offtime);
    }
}
