<?php

namespace Tests\Feature;

use App\Filament\Relations\EstimatesRelationManager;
use App\Filament\Resources\ProjectResource\Pages\EditProject;
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

class EstimatesRelationManagerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renders_for_a_project(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();

        Livewire::test(EstimatesRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => EditProject::class,
        ])->assertSuccessful();
    }

    #[Test]
    public function it_lists_the_projects_estimates(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();
        $estimates = Estimate::factory()->count(3)->for($project)->create();
        $otherProjectsEstimate = Estimate::factory()->create();

        Livewire::test(EstimatesRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => EditProject::class,
        ])
            ->assertCanSeeTableRecords($estimates)
            ->assertCanNotSeeTableRecords([$otherProjectsEstimate]);
    }

    #[Test]
    public function it_creates_an_estimate_for_the_project(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();

        Livewire::test(EstimatesRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => EditProject::class,
        ])
            ->callAction(TestAction::make(CreateAction::class)->table(true), data: [
                'project_id' => $project->getKey(),
                'title' => 'New scope of work',
                'amount' => 12.5,
            ])
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('estimates', [
            'project_id' => $project->getKey(),
            'title' => 'New scope of work',
        ]);
    }

    #[Test]
    public function it_requires_a_title_and_amount_when_creating_an_estimate(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();

        Livewire::test(EstimatesRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => EditProject::class,
        ])
            ->callAction(TestAction::make(CreateAction::class)->table(true), data: [
                'project_id' => $project->getKey(),
                'title' => '',
                'amount' => '',
            ])
            ->assertHasFormErrors(['title' => 'required', 'amount' => 'required']);

        $this->assertDatabaseCount('estimates', 0);
    }

    #[Test]
    public function it_updates_an_estimate(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();
        $estimate = Estimate::factory()->for($project)->create(['title' => 'Old title']);

        Livewire::test(EstimatesRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => EditProject::class,
        ])
            ->callAction(TestAction::make(EditAction::class)->table($estimate), data: ['title' => 'New title'])
            ->assertHasNoFormErrors();

        $this->assertSame('New title', $estimate->refresh()->title);
    }

    #[Test]
    public function it_requires_a_title_when_updating_an_estimate(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();
        $estimate = Estimate::factory()->for($project)->create();

        Livewire::test(EstimatesRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => EditProject::class,
        ])
            ->callAction(TestAction::make(EditAction::class)->table($estimate), data: ['title' => ''])
            ->assertHasFormErrors(['title' => 'required']);
    }

    #[Test]
    public function it_deletes_an_estimate(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();
        $estimate = Estimate::factory()->for($project)->create();

        Livewire::test(EstimatesRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => EditProject::class,
        ])
            ->callAction(TestAction::make(DeleteAction::class)->table($estimate));

        $this->assertModelMissing($estimate);
    }
}
