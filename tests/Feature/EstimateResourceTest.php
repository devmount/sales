<?php

namespace Tests\Feature;

use App\Filament\Resources\EstimateResource;
use App\Filament\Resources\EstimateResource\Pages\ListEstimates;
use App\Models\Estimate;
use App\Models\Project;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EstimateResourceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_redirects_guests_away_from_the_estimate_list(): void
    {
        $this->get(EstimateResource::getUrl('index'))->assertRedirect();
    }

    #[Test]
    public function it_renders_the_estimate_list_page_for_authenticated_users(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ListEstimates::class)->assertSuccessful();
    }

    #[Test]
    public function it_lists_estimates_in_the_table(): void
    {
        $this->actingAs(User::factory()->create());
        $estimates = Estimate::factory()->count(3)->create();

        Livewire::test(ListEstimates::class)
            ->loadTable()
            ->assertCanSeeTableRecords($estimates);
    }

    #[Test]
    public function it_creates_an_estimate(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();

        $data = [
            'project_id' => $project->getKey(),
            'title' => 'New scope of work',
            'description' => 'Some additional description',
            'amount' => 12.5,
            'weight' => 5,
        ];

        Livewire::test(ListEstimates::class)
            ->callAction(CreateAction::class, data: $data)
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('estimates', [
            'project_id' => $project->getKey(),
            'title' => 'New scope of work',
            'amount' => 12.5,
        ]);
    }

    #[Test]
    public function it_requires_a_project_title_and_amount_when_creating_an_estimate(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ListEstimates::class)
            ->callAction(CreateAction::class, data: [
                'project_id' => '',
                'title' => '',
                'amount' => '',
            ])
            ->assertHasFormErrors(['project_id' => 'required', 'title' => 'required', 'amount' => 'required']);

        $this->assertDatabaseCount('estimates', 0);
    }

    #[Test]
    public function it_updates_an_estimate(): void
    {
        $this->actingAs(User::factory()->create());
        $estimate = Estimate::factory()->create(['title' => 'Old title']);

        Livewire::test(ListEstimates::class)
            ->callAction(TestAction::make(EditAction::class)->table($estimate), data: ['title' => 'New title'])
            ->assertHasNoFormErrors();

        $this->assertSame('New title', $estimate->refresh()->title);
    }

    #[Test]
    public function it_requires_a_title_when_updating_an_estimate(): void
    {
        $this->actingAs(User::factory()->create());
        $estimate = Estimate::factory()->create();

        Livewire::test(ListEstimates::class)
            ->callAction(TestAction::make(EditAction::class)->table($estimate), data: ['title' => ''])
            ->assertHasFormErrors(['title' => 'required']);
    }

    #[Test]
    public function it_deletes_an_estimate_from_the_table(): void
    {
        $this->actingAs(User::factory()->create());
        $estimate = Estimate::factory()->create();

        Livewire::test(ListEstimates::class)
            ->callAction(TestAction::make(DeleteAction::class)->table($estimate));

        $this->assertModelMissing($estimate);
    }
}
