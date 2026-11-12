<?php

namespace Tests\Feature;

use App\Enums\ExpenseCategory;
use App\Enums\PricingUnit;
use App\Filament\Resources\ClientResource\Pages\ListClients;
use App\Filament\Resources\ExpenseResource\Pages\ListExpenses;
use App\Filament\Resources\InvoiceResource\Pages\ListInvoices;
use App\Filament\Resources\PositionResource\Widgets\RecentPositionsChart;
use App\Filament\Resources\ProjectResource\Pages\ListProjects;
use App\Filament\Resources\ProjectResource\Widgets\CurrentProjects;
use App\Filament\Widgets;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Position;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Ensures that the number of database queries doesn't grow with the number of records (no N+1 queries).
 */
class QueryCountTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create clients with an active project, each with a paid and an active invoice holding some positions
     */
    private function seedRecords(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $project = Project::factory()->for(Client::factory())->create([
                'start_at' => now()->subMonths(2),
                'due_at' => now()->addMonths(2),
                'aborted' => false,
            ]);
            foreach ([now()->subDays(5), null] as $paidAt) {
                $invoice = Invoice::factory()->for($project)->create([
                    'pricing_unit' => PricingUnit::Hour,
                    'transitory' => false,
                    'invoiced_at' => $paidAt,
                    'paid_at' => $paidAt,
                ]);
                foreach ([3, 10, 20] as $daysAgo) {
                    Position::factory()->for($invoice)->create([
                        'started_at' => now()->subDays($daysAgo)->setTime(9, 0),
                        'finished_at' => now()->subDays($daysAgo)->setTime(12, 0),
                    ]);
                }
            }
            Expense::factory()->create([
                'expended_at' => now()->subDays(5),
                'category' => ExpenseCategory::MinorAssets,
            ]);
        }
    }

    private function countQueries(string $component, bool $deferredTable): int
    {
        DB::flushQueryLog();
        $test = Livewire::test($component);
        if ($deferredTable) {
            $test->call('loadTable');
        }
        $test->assertSuccessful();
        return count(DB::getQueryLog());
    }

    public static function components(): array
    {
        return [
            'StatsOverview' => [Widgets\StatsOverview::class],
            'EventMatrix' => [Widgets\EventMatrix::class],
            'CurrentProjects' => [CurrentProjects::class],
            'RevenueExpensesChart' => [Widgets\RevenueExpensesChart::class],
            'MonthlyIncomeChart' => [Widgets\MonthlyIncomeChart::class],
            'HourlyRateChart' => [Widgets\HourlyRateChart::class],
            'ClientRevenueDistributionChart' => [Widgets\ClientRevenueDistributionChart::class],
            'ClientRevenueChart' => [Widgets\ClientRevenueChart::class],
            'ClientHoursChart' => [Widgets\ClientHoursChart::class],
            'SumProductiveHoursChart' => [Widgets\SumProductiveHoursChart::class],
            'WeeklyHoursChart' => [Widgets\WeeklyHoursChart::class],
            'OfftimeChart' => [Widgets\OfftimeChart::class],
            'RecentPositionsChart' => [RecentPositionsChart::class],
            'TaxOverview' => [Widgets\TaxOverview::class],
            'TaxReturnFormInput' => [Widgets\TaxReturnFormInput::class],
            'MinorAssetsList' => [Widgets\MinorAssetsList::class],
            'ListInvoices' => [ListInvoices::class, true],
            'ListClients' => [ListClients::class, true],
            'ListProjects' => [ListProjects::class, true],
            'ListExpenses' => [ListExpenses::class, true],
        ];
    }

    #[Test]
    #[DataProvider('components')]
    public function it_does_not_scale_queries_with_number_of_records(string $component, bool $deferredTable = false): void
    {
        $this->actingAs(User::factory()->create());
        DB::enableQueryLog();

        $this->seedRecords(2);
        $before = $this->countQueries($component, $deferredTable);

        $this->seedRecords(3);
        $after = $this->countQueries($component, $deferredTable);

        $this->assertSame($before, $after, "$component query count grew from $before to $after");
    }
}
