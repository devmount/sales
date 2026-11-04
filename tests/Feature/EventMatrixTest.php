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
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class EventMatrixTest extends TestCase
{
    use RefreshDatabase;

    private function getDays(string $mode = 'total', string $year = ''): array
    {
        $widget = new EventMatrix();
        $widget->mode = $mode;
        $widget->year = $year;
        return (new ReflectionMethod($widget, 'getDays'))->invoke($widget);
    }

    private function createPosition(string $date, string $clientColor = '#123456', string $title = 'Matrix project'): Position
    {
        $client = Client::factory()->create(['color' => $clientColor]);
        $project = Project::factory()->for($client)->create(['title' => $title]);
        $invoice = Invoice::factory()->for($project)->create([
            'price' => 100,
            'pricing_unit' => PricingUnit::Hour,
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
            ->set('mode', 'offtimes')
            ->set('year', (string) now()->year)
            ->assertSuccessful();
    }

    #[Test]
    public function it_sums_earnings_minus_expenses_per_day(): void
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

        $days = $this->getDays('total');

        $this->assertSame(400.0, $days[$date]['value']);
        $this->assertFalse($days[$date]['negative']);
        $this->assertSame(4, $days[$date]['level']);
        $this->assertSame(500.0, $this->getDays('earnings')[$date]['value']);
        $this->assertSame(100.0, $this->getDays('expenses')[$date]['value']);
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
        $this->assertSame(-50.0, $this->getDays('total')[$date]['value']);
        $this->assertTrue($this->getDays('total')[$date]['negative']);
    }

    #[Test]
    public function it_marks_every_day_of_a_multiday_offtime(): void
    {
        $start = now()->subDays(20);

        Offtime::factory()->create([
            'start' => $start->format('Y-m-d'),
            'end' => $start->copy()->addDays(2)->format('Y-m-d'),
            'category' => OfftimeCategory::Sick,
        ]);

        $offtimes = $this->getDays('offtimes');
        $total = $this->getDays('total');

        foreach ([0, 1, 2] as $offset) {
            $key = $start->copy()->addDays($offset)->format('Y-m-d');
            $this->assertSame(1.0, $offtimes[$key]['value']);
            $this->assertSame(OfftimeCategory::Sick, $total[$key]['offtime']);
        }

        $after = $start->copy()->addDays(3)->format('Y-m-d');
        $this->assertSame(0.0, $offtimes[$after]['value']);
        $this->assertNull($total[$after]['offtime']);
    }

    #[Test]
    public function it_shows_last_365_days_by_default_and_full_year_when_selected(): void
    {
        $days = $this->getDays();

        $this->assertCount(365, $days);
        $this->assertSame(now()->format('Y-m-d'), array_key_last($days));

        $days = $this->getDays('total', '2024');

        $this->assertCount(366, $days);
        $this->assertSame('2024-01-01', array_key_first($days));
        $this->assertSame('2024-12-31', array_key_last($days));
    }

    #[Test]
    public function it_lists_position_with_client_color_in_tooltip(): void
    {
        $date = now()->subDays(3)->format('Y-m-d');
        $this->createPosition($date, '#abcdef', 'Tooltip project');

        $tooltip = $this->getDays()[$date]['tooltip'];

        $this->assertStringContainsString('Tooltip project', $tooltip);
        $this->assertStringContainsString('#abcdef', $tooltip);
    }
}
