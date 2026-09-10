<?php

namespace Tests\Feature;

use App\Enums\PricingUnit;
use App\Filament\Widgets\MonthlyIncomeChart;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class MonthlyIncomeChartTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renders_successfully(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(MonthlyIncomeChart::class)->assertSuccessful();
    }

    #[Test]
    public function it_averages_net_income_per_month_for_the_year(): void
    {
        $year = now()->year - 1;
        Invoice::factory()
            ->for(Project::factory())
            ->create(['paid_at' => "$year-06-01", 'transitory' => false, 'pricing_unit' => PricingUnit::Project, 'price' => 1000, 'discount' => null, 'taxable' => false]);

        $widget = new MonthlyIncomeChart();
        $widget->filter = 'net';
        $data = (new ReflectionMethod($widget, 'getData'))->invoke($widget);

        $yearIndex = array_search((string) $year, $data['labels'], true);

        $this->assertNotFalse($yearIndex);
        $this->assertSame(83.33, $data['datasets'][0]['data'][$yearIndex]);
    }

    #[Test]
    public function it_averages_gross_income_per_month_for_the_year(): void
    {
        $year = now()->year - 1;
        Invoice::factory()
            ->for(Project::factory())
            ->create([
                'paid_at' => "$year-06-01",
                'transitory' => false,
                'pricing_unit' => PricingUnit::Project,
                'price' => 1000,
                'discount' => null,
                'taxable' => true,
                'vat_rate' => 0.19,
            ]);

        $widget = new MonthlyIncomeChart();
        $widget->filter = 'gross';
        $data = (new ReflectionMethod($widget, 'getData'))->invoke($widget);

        $yearIndex = array_search((string) $year, $data['labels'], true);

        $this->assertNotFalse($yearIndex);
        $this->assertSame(99.17, $data['datasets'][0]['data'][$yearIndex]);
    }

    #[Test]
    public function it_does_not_double_count_an_invoice_paid_exactly_on_a_year_boundary(): void
    {
        $anchorYear = now()->year - 4;
        $boundaryYear = now()->year - 2;

        // establishes the chart's period start well before the boundary under test
        Invoice::factory()
            ->for(Project::factory())
            ->create(['paid_at' => "$anchorYear-01-15", 'transitory' => false, 'pricing_unit' => PricingUnit::Project, 'price' => 12, 'discount' => null, 'taxable' => false]);

        // paid exactly on the year boundary: must count only for $boundaryYear, not also for $boundaryYear - 1
        Invoice::factory()
            ->for(Project::factory())
            ->create(['paid_at' => "$boundaryYear-01-01", 'transitory' => false, 'pricing_unit' => PricingUnit::Project, 'price' => 1200, 'discount' => null, 'taxable' => false]);

        $widget = new MonthlyIncomeChart();
        $widget->filter = 'net';
        $data = (new ReflectionMethod($widget, 'getData'))->invoke($widget);

        $previousYearIndex = array_search((string) ($boundaryYear - 1), $data['labels'], true);
        $boundaryYearIndex = array_search((string) $boundaryYear, $data['labels'], true);

        $this->assertNotFalse($previousYearIndex);
        $this->assertNotFalse($boundaryYearIndex);
        $this->assertSame(0.0, $data['datasets'][0]['data'][$previousYearIndex]);
        $this->assertSame(100.0, $data['datasets'][0]['data'][$boundaryYearIndex]);
    }
}
