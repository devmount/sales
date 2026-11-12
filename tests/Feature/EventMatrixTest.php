<?php

namespace Tests\Feature;

use App\Enums\ExpenseCategory;
use App\Enums\OfftimeCategory;
use App\Enums\PricingUnit;
use App\Filament\Widgets\EventMatrix;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Offtime;
use App\Models\Position;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Number;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class EventMatrixTest extends TestCase
{
    use RefreshDatabase;

    private function getDays(string $mode = 'profit', string $year = '', bool $offtimes = true): array
    {
        $widget = new EventMatrix();
        $widget->mode = $mode;
        $widget->year = $year;
        $widget->offtimes = $offtimes;
        return (new ReflectionMethod($widget, 'getDays'))->invoke($widget);
    }

    private function createPosition(string $date, string $clientColor = '#123456', string $title = 'Matrix project'): Position
    {
        $client = Client::factory()->create(['color' => $clientColor]);
        $project = Project::factory()->for($client)->create(['title' => $title]);
        $invoice = Invoice::factory()->for($project)->create([
            'price' => 100,
            'pricing_unit' => PricingUnit::Hour,
            'discount' => null,
            'transitory' => false,
        ]);

        return Position::factory()->for($invoice)->create([
            'started_at' => "$date 09:00:00",
            'finished_at' => "$date 14:00:00",
            'pause_duration' => 0,
        ]);
    }

    #[Test]
    public function it_renders_successfully(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(EventMatrix::class)
            ->assertSuccessful()
            ->set('offtimes', false)
            ->set('year', (string) now()->year)
            ->assertSuccessful()
            ->set('mode', 'hours')
            ->assertSuccessful()
            ->assertSee(__('profit'))
            ->assertSee(__('revenue'))
            ->assertSee(trans_choice('hour', 2))
            ->assertSee(trans_choice('offtime', 2))
            ->assertDontSeeHtml('<option value="offtimes">')
            ->assertSeeHtml('role="switch"');
    }

    #[Test]
    public function it_sums_revenue_minus_expenses_per_day(): void
    {
        $date = now()->subDays(10)->format('Y-m-d');
        $this->createPosition($date);

        Expense::factory()->notTaxable()->create([
            'expended_at' => $date,
            'price' => 100,
            'quantity' => 1,
            'category' => ExpenseCategory::Good,
        ]);
        Expense::factory()->notTaxable()->create([
            'expended_at' => $date,
            'price' => 1000,
            'quantity' => 1,
            'category' => ExpenseCategory::Vat,
        ]);

        $days = $this->getDays('profit');

        $this->assertSame(400.0, $days[$date]['value']);
        $this->assertFalse($days[$date]['negative']);
        $this->assertSame(4, $days[$date]['level']);
        $this->assertSame(500.0, $this->getDays('revenue')[$date]['value']);
        $this->assertSame(100.0, $this->getDays('expenses')[$date]['value']);
    }

    #[Test]
    public function it_sums_worked_hours_per_day_in_hours_mode(): void
    {
        $date = now()->subDays(10)->format('Y-m-d');
        $this->createPosition($date, '#abcdef', 'Hours project');
        $this->createPosition($date);

        Expense::factory()->notTaxable()->create([
            'expended_at' => $date,
            'price' => 100,
            'quantity' => 1,
            'category' => ExpenseCategory::Good,
        ]);

        $day = $this->getDays('hours')[$date];

        $this->assertSame(10.0, $day['value']);
        $this->assertSame(4, $day['level']);
        $this->assertStringContainsString('Hours project', $day['tooltip']);
        $this->assertStringContainsString('#abcdef', $day['tooltip']);
        $this->assertStringContainsString('5 ' . trans_choice('hour', 5), $day['tooltip']);
        $this->assertStringContainsString('10 ' . trans_choice('hour', 10), $day['tooltip']);
        $this->assertStringNotContainsString('€', $day['tooltip']);
    }

    #[Test]
    public function it_sums_all_values_of_the_current_view(): void
    {
        $this->createPosition(now()->subDays(10)->format('Y-m-d'));
        $this->createPosition(now()->subDays(20)->format('Y-m-d'));
        $this->createPosition(now()->subYears(2)->format('Y-m-d'));

        Expense::factory()->notTaxable()->create([
            'expended_at' => now()->subDays(5)->format('Y-m-d'),
            'price' => 100,
            'quantity' => 1,
            'category' => ExpenseCategory::Good,
        ]);

        $sum = function (string $mode): string {
            $widget = new EventMatrix();
            $widget->mode = $mode;
            $days = (new ReflectionMethod($widget, 'getDays'))->invoke($widget);
            return (new ReflectionMethod($widget, 'getSum'))->invoke($widget, $days);
        };

        $this->assertSame('10 ' . trans_choice('hour', 10), $sum('hours'));
        $this->assertSame(Number::currency(1000, 'EUR', locale: app()->getLocale()), $sum('revenue'));
        $this->assertSame(Number::currency(900, 'EUR', locale: app()->getLocale()), $sum('profit'));
        $this->assertSame(Number::currency(-100, 'EUR', locale: app()->getLocale()), $sum('expenses'));
    }

    #[Test]
    public function it_applies_discounts_and_excludes_transitory_invoices(): void
    {
        $date = now()->subDays(10)->format('Y-m-d');
        $this->createPosition($date)->invoice->update(['discount' => 50]);
        $this->createPosition($date)->invoice->update(['transitory' => true]);

        $this->assertSame(450.0, $this->getDays('revenue')[$date]['value']);
        $this->assertSame(450.0, $this->getDays('profit')[$date]['value']);
        $this->assertSame(5.0, $this->getDays('hours')[$date]['value']);
    }

    #[Test]
    public function it_only_includes_tax_expenses_in_taxes_mode(): void
    {
        $date = now()->subDays(5)->format('Y-m-d');

        Expense::factory()->notTaxable()->create([
            'expended_at' => $date,
            'price' => 300,
            'quantity' => 1,
            'category' => ExpenseCategory::Tax,
        ]);
        Expense::factory()->notTaxable()->create([
            'expended_at' => $date,
            'price' => 50,
            'quantity' => 1,
            'category' => ExpenseCategory::Service,
        ]);

        $this->assertSame(300.0, $this->getDays('taxes')[$date]['value']);
        $this->assertSame(-50.0, $this->getDays('profit')[$date]['value']);
        $this->assertTrue($this->getDays('profit')[$date]['negative']);
    }

    #[Test]
    public function it_marks_every_day_of_a_multiday_offtime_in_any_mode(): void
    {
        $start = now()->subDays(20);

        Offtime::factory()->create([
            'start' => $start->format('Y-m-d'),
            'end' => $start->copy()->addDays(2)->format('Y-m-d'),
            'category' => OfftimeCategory::Sick,
        ]);

        foreach (['profit', 'revenue', 'hours', 'expenses', 'taxes'] as $mode) {
            $days = $this->getDays($mode);
            foreach ([0, 1, 2] as $offset) {
                $key = $start->copy()->addDays($offset)->format('Y-m-d');
                $this->assertSame(OfftimeCategory::Sick, $days[$key]['offtime']);
                $this->assertSame(0.0, $days[$key]['value']);
            }
            $this->assertNull($days[$start->copy()->addDays(3)->format('Y-m-d')]['offtime']);
        }
    }

    #[Test]
    public function it_hides_offtimes_when_toggled_off(): void
    {
        $date = now()->subDays(20)->format('Y-m-d');

        Offtime::factory()->create([
            'start' => $date,
            'end' => $date,
            'category' => OfftimeCategory::Sick,
            'description' => 'Hidden offtime',
        ]);

        $day = $this->getDays('profit', '', false)[$date];

        $this->assertNull($day['offtime']);
        $this->assertStringNotContainsString('Hidden offtime', $day['tooltip']);
    }

    #[Test]
    public function it_shows_last_365_days_by_default_and_full_year_when_selected(): void
    {
        $days = $this->getDays();

        $this->assertCount(365, $days);
        $this->assertSame(now()->format('Y-m-d'), array_key_last($days));

        $days = $this->getDays('profit', '2024');

        $this->assertCount(366, $days);
        $this->assertSame('2024-01-01', array_key_first($days));
        $this->assertSame('2024-12-31', array_key_last($days));
    }

    #[Test]
    public function it_places_days_in_weekday_rows_and_week_columns_with_month_labels(): void
    {
        $widget = new EventMatrix();
        $widget->year = '2025';
        $days = (new ReflectionMethod($widget, 'getDays'))->invoke($widget);
        $months = (new ReflectionMethod($widget, 'getMonths'))->invoke($widget, $days);

        // 2025-01-01 is a Wednesday: first week column, third weekday row (after month row)
        $this->assertSame(2, $days['2025-01-01']['col']);
        $this->assertSame(4, $days['2025-01-01']['row']);
        // 2025-01-06 is the following Monday
        $this->assertSame(3, $days['2025-01-06']['col']);
        $this->assertSame(2, $days['2025-01-06']['row']);

        $this->assertCount(12, $months);
        $this->assertSame($days['2025-01-01']['date']->isoFormat('MMM'), $months[2]);
        $this->assertSame($days['2025-02-01']['date']->isoFormat('MMM'), $months[$days['2025-02-01']['col']]);
    }

    #[Test]
    public function it_lists_position_with_client_color_in_tooltip(): void
    {
        $date = now()->subDays(3)->format('Y-m-d');
        $this->createPosition($date, '#abcdef', 'Tooltip project');

        $tooltip = $this->getDays()[$date]['tooltip'];

        $this->assertStringContainsString('Tooltip project', $tooltip);
        $this->assertStringContainsString('#abcdef', $tooltip);
        $this->assertStringNotContainsString(__('total'), $tooltip);
    }

    #[Test]
    public function it_adds_a_total_line_to_tooltips_with_multiple_entries(): void
    {
        $date = now()->subDays(3)->format('Y-m-d');
        $this->createPosition($date);
        $this->createPosition($date);

        $tooltip = $this->getDays()[$date]['tooltip'];

        $this->assertStringContainsString(__('total'), $tooltip);
        $this->assertStringContainsString('var(--gray-400)', $tooltip);
    }
}
