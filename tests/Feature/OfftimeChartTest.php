<?php

namespace Tests\Feature;

use App\Enums\OfftimeCategory;
use App\Filament\Widgets\OfftimeChart;
use App\Models\Invoice;
use App\Models\Offtime;
use App\Models\Position;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class OfftimeChartTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renders_successfully(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(OfftimeChart::class)->assertSuccessful();
    }

    #[Test]
    public function it_assembles_offtime_and_working_days_per_year(): void
    {
        $year = now()->year - 1;

        Invoice::factory()
            ->for(Project::factory())
            ->create(['paid_at' => "$year-01-15", 'transitory' => false]);

        Offtime::factory()->create([
            'start' => "$year-04-10",
            'end' => null,
            'category' => OfftimeCategory::Vacation,
        ]);

        Position::factory()->create([
            'started_at' => "$year-04-11 09:00:00",
            'finished_at' => "$year-04-11 14:00:00",
            'pause_duration' => 0,
        ]);

        $widget = new OfftimeChart();
        $data = (new ReflectionMethod($widget, 'getData'))->invoke($widget);

        $yearIndex = array_search((string) $year, $data['labels'], true);
        $weekend = collect($data['datasets'])->firstWhere('label', __('weekendDays'));
        $planned = collect($data['datasets'])->firstWhere('label', __('plannedDaysOff'));
        $unplanned = collect($data['datasets'])->firstWhere('label', __('unplannedDaysOff'));
        $total = collect($data['datasets'])->firstWhere('label', __('totalDaysOff'));
        $worked = collect($data['datasets'])->firstWhere('label', __('workingDays'));

        $this->assertNotFalse($yearIndex);
        $this->assertSame(1, $planned['data'][$yearIndex]);
        $this->assertSame(0, $unplanned['data'][$yearIndex]);
        $this->assertSame(
            $weekend['data'][$yearIndex] + $planned['data'][$yearIndex] + $unplanned['data'][$yearIndex],
            $total['data'][$yearIndex]
        );
        $this->assertSame(1, $worked['data'][$yearIndex]);
    }
}
