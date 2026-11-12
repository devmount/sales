<?php

namespace Tests\Feature;

use App\Filament\Resources\PositionResource\Widgets\RecentPositionsChart;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Position;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class RecentPositionsChartTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renders_successfully(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(RecentPositionsChart::class)->assertSuccessful();
    }

    #[Test]
    public function it_groups_recent_hours_worked_by_project(): void
    {
        $client = Client::factory()->create(['color' => '#123456']);
        $project = Project::factory()->for($client)->create(['title' => 'Recent project']);
        $invoice = Invoice::factory()->for($project)->create(['transitory' => false]);

        $today = now();
        Position::factory()->for($invoice)->create([
            'started_at' => $today->copy()->setTime(9, 0),
            'finished_at' => $today->copy()->setTime(14, 0),
            'pause_duration' => 0,
        ]);

        // transitory work must be excluded
        Position::factory()->for(Invoice::factory()->for($project)->create(['transitory' => true]))->create([
            'started_at' => $today->copy()->setTime(15, 0),
            'finished_at' => $today->copy()->setTime(17, 0),
            'pause_duration' => 0,
        ]);

        $widget = new RecentPositionsChart();
        $widget->filter = '30';
        $data = (new ReflectionMethod($widget, 'getData'))->invoke($widget);

        $dataset = collect($data['datasets'])->firstWhere('label', 'Recent project');
        $todayIndex = array_search($today->isoFormat('dd, D. MMM'), $data['labels'], true);

        $this->assertNotFalse($todayIndex);
        $this->assertNotNull($dataset);
        $this->assertSame(5.0, $dataset['data'][$todayIndex]);
        $this->assertSame('#123456', $dataset['backgroundColor']);
    }

    #[Test]
    public function it_sums_multiple_positions_of_a_project_on_the_same_day(): void
    {
        $project = Project::factory()->create(['title' => 'Busy project']);
        $invoice = Invoice::factory()->for($project)->create(['transitory' => false]);

        $today = now();
        Position::factory()->for($invoice)->create([
            'started_at' => $today->copy()->setTime(8, 0),
            'finished_at' => $today->copy()->setTime(10, 0),
            'pause_duration' => 0,
        ]);
        Position::factory()->for($invoice)->create([
            'started_at' => $today->copy()->setTime(13, 0),
            'finished_at' => $today->copy()->setTime(16, 0),
            'pause_duration' => 0,
        ]);

        $widget = new RecentPositionsChart();
        $widget->filter = '30';
        $data = (new ReflectionMethod($widget, 'getData'))->invoke($widget);

        $dataset = collect($data['datasets'])->firstWhere('label', 'Busy project');
        $todayIndex = array_search($today->isoFormat('dd, D. MMM'), $data['labels'], true);

        $this->assertSame(5.0, $dataset['data'][$todayIndex]);
    }
}
