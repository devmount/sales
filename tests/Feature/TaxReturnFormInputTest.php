<?php

namespace Tests\Feature;

use App\Enums\ExpenseCategory;
use App\Enums\PricingUnit;
use App\Filament\Widgets\TaxReturnFormInput;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxReturnFormInputTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renders_successfully(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(TaxReturnFormInput::class)->assertSuccessful();
    }

    #[Test]
    public function it_defaults_the_filter_to_last_year(): void
    {
        $widget = new TaxReturnFormInput();

        $this->assertSame(now()->year - 1, $widget->filter);
    }

    #[Test]
    public function it_computes_the_tax_return_lines_for_the_selected_year(): void
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

        Expense::factory()->create([
            'expended_at' => "$year-06-01",
            'category' => ExpenseCategory::Good,
            'price' => 100,
            'quantity' => 1,
            'taxable' => true,
            'vat_rate' => 0.19,
        ]);

        Expense::factory()->create([
            'expended_at' => "$year-06-01",
            'category' => ExpenseCategory::Rent,
            'price' => 50,
            'quantity' => 1,
            'taxable' => false,
        ]);

        $widget = new TaxReturnFormInput();
        $widget->filter = $year;
        $records = $widget->getTableRecords()->keyBy('__key');

        $this->assertSame(1000.0, $records[2]['value']); // rsc14 - net earned
        $this->assertSame(0, $records[3]['value']); // rsc16 - net untaxable earned
        $this->assertSame(190.0, $records[4]['value']); // rsc17 - vat earned
        $this->assertSame(84.03, $records[5]['value']); // rsc27 - net goods/services expended
        $this->assertSame(50.0, $records[8]['value']); // rsc65a - rent expended
        $this->assertEqualsWithDelta(866.0, $records[1]['value'], 0.01); // itr1 - taxable profit
    }
}
