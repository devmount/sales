<?php

namespace Tests\Feature;

use App\Enums\ExpenseCategory;
use App\Enums\PricingUnit;
use App\Filament\Widgets\SalesChart;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class SalesChartTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renders_successfully(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(SalesChart::class)->assertSuccessful();
    }

    #[Test]
    public function it_sums_income_expenses_and_taxes_per_year(): void
    {
        $year = now()->year - 1;

        Invoice::factory()
            ->for(Project::factory())
            ->create(['paid_at' => "$year-06-01", 'transitory' => false, 'pricing_unit' => PricingUnit::Project, 'price' => 500, 'discount' => null]);

        Expense::factory()->create([
            'expended_at' => "$year-06-01",
            'category' => ExpenseCategory::Good,
            'price' => 100,
            'quantity' => 1,
            'taxable' => false,
        ]);

        Expense::factory()->create([
            'expended_at' => "$year-06-01",
            'category' => ExpenseCategory::Vat,
            'price' => 50,
            'quantity' => 1,
            'taxable' => false,
        ]);

        Expense::factory()->create([
            'expended_at' => "$year-06-01",
            'category' => ExpenseCategory::Rent,
            'price' => 100,
            'quantity' => 1,
            'taxable' => false,
            'taxable_ratio' => 0.5,
        ]);

        $widget = new SalesChart();
        $widget->filter = 'y';
        $data = (new ReflectionMethod($widget, 'getData'))->invoke($widget);

        $yearIndex = array_search((string) $year, $data['labels'], true);
        $income = collect($data['datasets'])->firstWhere('label', __('income'));
        $expense = collect($data['datasets'])->firstWhere('label', trans_choice('expense', 2));
        $taxes = collect($data['datasets'])->firstWhere('label', __('taxes'));

        $this->assertNotFalse($yearIndex);
        $this->assertSame(500.0, $income['data'][$yearIndex]);
        // 100 (good, full ratio) + 50 (rent, pro-rated by taxable_ratio 0.5)
        $this->assertSame(150.0, $expense['data'][$yearIndex]);
        $this->assertSame(50.0, $taxes['data'][$yearIndex]);
    }

    #[Test]
    public function it_does_not_double_count_an_invoice_paid_exactly_on_a_year_boundary(): void
    {
        $anchorYear = now()->year - 4;
        $boundaryYear = now()->year - 2;

        // establishes the chart's period start well before the boundary under test
        Invoice::factory()
            ->for(Project::factory())
            ->create(['paid_at' => "$anchorYear-01-15", 'transitory' => false, 'pricing_unit' => PricingUnit::Project, 'price' => 1, 'discount' => null]);

        // paid exactly on the year boundary: must count only for $boundaryYear, not also for $boundaryYear - 1
        Invoice::factory()
            ->for(Project::factory())
            ->create(['paid_at' => "$boundaryYear-01-01", 'transitory' => false, 'pricing_unit' => PricingUnit::Project, 'price' => 1200, 'discount' => null]);

        $widget = new SalesChart();
        $widget->filter = 'y';
        $data = (new ReflectionMethod($widget, 'getData'))->invoke($widget);

        $income = collect($data['datasets'])->firstWhere('label', __('income'));
        $previousYearIndex = array_search((string) ($boundaryYear - 1), $data['labels'], true);
        $boundaryYearIndex = array_search((string) $boundaryYear, $data['labels'], true);

        $this->assertNotFalse($previousYearIndex);
        $this->assertNotFalse($boundaryYearIndex);
        $this->assertEquals(0, $income['data'][$previousYearIndex]);
        $this->assertSame(1200.0, $income['data'][$boundaryYearIndex]);
    }
}
