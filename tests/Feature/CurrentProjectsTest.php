<?php

namespace Tests\Feature;

use App\Filament\Resources\ProjectResource\Widgets\CurrentProjects;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class CurrentProjectsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renders_successfully(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CurrentProjects::class)->assertSuccessful();
    }

    #[Test]
    public function it_only_lists_currently_active_projects(): void
    {
        $active = Project::factory()->create([
            'start_at' => now()->subMonth(),
            'due_at' => now()->addMonth(),
            'aborted' => false,
        ]);
        $abortedProject = Project::factory()->create([
            'start_at' => now()->subMonth(),
            'due_at' => now()->addMonth(),
            'aborted' => true,
        ]);
        $finishedProject = Project::factory()->create([
            'start_at' => now()->subMonths(3),
            'due_at' => now()->subMonth(),
            'aborted' => false,
        ]);

        $widget = new CurrentProjects();
        $data = (new ReflectionMethod($widget, 'getViewData'))->invoke($widget);
        $projectIds = $data['projects']->pluck('id');

        $this->assertTrue($projectIds->contains($active->id));
        $this->assertFalse($projectIds->contains($abortedProject->id));
        $this->assertFalse($projectIds->contains($finishedProject->id));
    }
}
