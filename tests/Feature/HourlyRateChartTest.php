<?php

namespace Tests\Feature;

use App\Enums\PricingUnit;
use App\Filament\Widgets\HourlyRateChart;
use App\Models\Invoice;
use App\Models\Position;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class HourlyRateChartTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renders_successfully(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(HourlyRateChart::class)->assertSuccessful();
    }

    #[Test]
    public function it_computes_the_average_hourly_rate_per_year(): void
    {
        $year = now()->year - 1;

        $invoiceA = Invoice::factory()
            ->for(Project::factory())
            ->create(['paid_at' => "$year-03-01", 'transitory' => false, 'pricing_unit' => PricingUnit::Hour, 'price' => 100, 'discount' => null]);
        Position::factory()->for($invoiceA)->withDuration(4)->create(['pause_duration' => 0]);

        $invoiceB = Invoice::factory()
            ->for(Project::factory())
            ->create(['paid_at' => "$year-09-01", 'transitory' => false, 'pricing_unit' => PricingUnit::Hour, 'price' => 50, 'discount' => null]);
        Position::factory()->for($invoiceB)->withDuration(4)->create(['pause_duration' => 0]);

        $widget = new HourlyRateChart();
        $data = (new ReflectionMethod($widget, 'getData'))->invoke($widget);

        $yearIndex = array_search((string) $year, $data['labels'], true);

        // total net 400 (4h*100) + 200 (4h*50) = 600, over 8 hours => 75 €/h average
        $this->assertNotFalse($yearIndex);
        $this->assertSame(75.0, $data['datasets'][0]['data'][$yearIndex]);
    }
}
