<?php

namespace Tests\Feature;

use App\Filament\Widgets\WeeklyHoursChart;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class WeeklyHoursChartTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renders_successfully(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(WeeklyHoursChart::class)->assertSuccessful();
    }

    #[Test]
    public function it_computes_average_weekly_hours_and_week_count_per_year(): void
    {
        $year = now()->year - 1;
        Position::factory()->create(['started_at' => "$year-02-02 09:00:00", 'finished_at' => "$year-02-02 19:00:00", 'pause_duration' => 0]);
        Position::factory()->create(['started_at' => "$year-02-16 09:00:00", 'finished_at' => "$year-02-16 19:00:00", 'pause_duration' => 0]);

        $widget = new WeeklyHoursChart();
        $data = (new ReflectionMethod($widget, 'getData'))->invoke($widget);

        $yearIndex = array_search((string) $year, $data['labels'], true);
        $hoursDataset = collect($data['datasets'])->firstWhere('label', __('hours/week'));
        $weeksDataset = collect($data['datasets'])->firstWhere('label', __('weeksWithWorkingDays'));

        $this->assertNotFalse($yearIndex);
        $this->assertSame(10.0, $hoursDataset['data'][$yearIndex]);
        $this->assertSame(2, $weeksDataset['data'][$yearIndex]);
    }
}
