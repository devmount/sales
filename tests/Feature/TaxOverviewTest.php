<?php

namespace Tests\Feature;

use App\Enums\ExpenseCategory;
use App\Enums\PricingUnit;
use App\Filament\Widgets\TaxOverview;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxOverviewTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renders_successfully(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(TaxOverview::class)->assertSuccessful();
    }

    #[Test]
    public function it_summarizes_taxable_net_and_vat_for_the_current_month(): void
    {
        Invoice::factory()
            ->for(Project::factory())
            ->create([
                'paid_at' => now(),
                'transitory' => false,
                'pricing_unit' => PricingUnit::Project,
                'price' => 1000,
                'discount' => null,
                'taxable' => true,
                'vat_rate' => 0.19,
            ]);

        Expense::factory()->create([
            'expended_at' => now(),
            'category' => ExpenseCategory::Good,
            'price' => 100,
            'quantity' => 1,
            'taxable' => true,
            'vat_rate' => 0.19,
        ]);

        $widget = new TaxOverview();
        $record = $widget->getTableRecords()->first();

        $this->assertSame(1000.0, $record['netTaxable']);
        $this->assertEquals(0, $record['netUntaxable']);
        $this->assertSame(1000.0, $record['totalNet']);
        $this->assertSame(15.97, $record['vatExpenses']);
        $this->assertSame(174.03, $record['totalVat']);
    }

    #[Test]
    public function it_disables_the_last_advance_vat_action_when_it_already_exists(): void
    {
        Expense::saveLastAdvanceVat();

        $widget = new TaxOverview();

        $this->assertTrue($widget->lastAdvanceVatAction()->isDisabled());
    }
}
