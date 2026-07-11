<?php

namespace Tests\Feature;

use App\Enums\PricingUnit;
use App\Filament\Relations\ProjectsRelationManager;
use App\Filament\Resources\ClientResource\Pages\EditClient;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProjectsRelationManagerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renders_for_a_client(): void
    {
        $this->actingAs(User::factory()->create());
        $client = Client::factory()->create();

        Livewire::test(ProjectsRelationManager::class, [
            'ownerRecord' => $client,
            'pageClass' => EditClient::class,
        ])->assertSuccessful();
    }

    #[Test]
    public function it_lists_the_clients_projects(): void
    {
        $this->actingAs(User::factory()->create());
        $client = Client::factory()->create();
        $projects = Project::factory()->count(3)->for($client)->create();
        $otherClientsProject = Project::factory()->create();

        Livewire::test(ProjectsRelationManager::class, [
            'ownerRecord' => $client,
            'pageClass' => EditClient::class,
        ])
            ->assertCanSeeTableRecords($projects)
            ->assertCanNotSeeTableRecords([$otherClientsProject]);
    }

    #[Test]
    public function it_creates_a_project_for_the_client(): void
    {
        $this->actingAs(User::factory()->create());
        $client = Client::factory()->create();

        Livewire::test(ProjectsRelationManager::class, [
            'ownerRecord' => $client,
            'pageClass' => EditClient::class,
        ])
            ->callAction(TestAction::make(CreateAction::class)->table(true), data: [
                'client_id' => $client->getKey(),
                'title' => 'New project',
                'start_at' => '2026-01-01',
                'scope' => 10,
                'price' => 50,
                'pricing_unit' => PricingUnit::Hour->value,
            ])
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('projects', [
            'client_id' => $client->getKey(),
            'title' => 'New project',
        ]);
    }

    #[Test]
    public function it_requires_a_title_start_scope_price_and_pricing_unit_when_creating_a_project(): void
    {
        $this->actingAs(User::factory()->create());
        $client = Client::factory()->create();

        Livewire::test(ProjectsRelationManager::class, [
            'ownerRecord' => $client,
            'pageClass' => EditClient::class,
        ])
            ->callAction(TestAction::make(CreateAction::class)->table(true), data: [
                'client_id' => $client->getKey(),
                'title' => '',
                'start_at' => '',
                'scope' => '',
                'price' => '',
                'pricing_unit' => '',
            ])
            ->assertHasFormErrors([
                'title' => 'required',
                'start_at' => 'required',
                'scope' => 'required',
                'price' => 'required',
                'pricing_unit' => 'required',
            ]);

        $this->assertDatabaseCount('projects', 0);
    }

    #[Test]
    public function it_updates_a_project(): void
    {
        $this->actingAs(User::factory()->create());
        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create(['title' => 'Old title']);

        Livewire::test(ProjectsRelationManager::class, [
            'ownerRecord' => $client,
            'pageClass' => EditClient::class,
        ])
            ->callAction(TestAction::make(EditAction::class)->table($project), data: ['title' => 'New title'])
            ->assertHasNoFormErrors();

        $this->assertSame('New title', $project->refresh()->title);
    }

    #[Test]
    public function it_requires_a_title_when_updating_a_project(): void
    {
        $this->actingAs(User::factory()->create());
        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create();

        Livewire::test(ProjectsRelationManager::class, [
            'ownerRecord' => $client,
            'pageClass' => EditClient::class,
        ])
            ->callAction(TestAction::make(EditAction::class)->table($project), data: ['title' => ''])
            ->assertHasFormErrors(['title' => 'required']);
    }
}
