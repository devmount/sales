<?php

namespace Tests\Feature;

use App\Filament\Widgets\SumProductiveHoursChart;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class SumProductiveHoursChartTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renders_successfully(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(SumProductiveHoursChart::class)->assertSuccessful();
    }

    #[Test]
    public function it_sums_worked_hours_per_year(): void
    {
        $year = now()->year - 1;
        Position::factory()->create(['started_at' => "$year-02-01 09:00:00", 'finished_at' => "$year-02-01 14:00:00", 'pause_duration' => 0]);
        Position::factory()->create(['started_at' => "$year-11-01 09:00:00", 'finished_at' => "$year-11-01 12:00:00", 'pause_duration' => 0]);

        $widget = new SumProductiveHoursChart();
        $widget->filter = 'y';
        $data = (new ReflectionMethod($widget, 'getData'))->invoke($widget);

        $yearIndex = array_search((string) $year, $data['labels'], true);

        $this->assertNotFalse($yearIndex);
        $this->assertSame(8.0, $data['datasets'][0]['data'][$yearIndex]);
    }
}
