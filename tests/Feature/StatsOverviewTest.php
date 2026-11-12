<?php

namespace Tests\Feature;

use App\Enums\PricingUnit;
use App\Filament\Widgets\StatsOverview;
use App\Models\Invoice;
use App\Models\Position;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class StatsOverviewTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renders_successfully(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(StatsOverview::class)->assertSuccessful();
    }

    #[Test]
    public function it_sums_revenue_and_hours_worked_in_the_current_week(): void
    {
        $mondayThisWeek = now()->startOfWeek(Carbon::MONDAY);

        Position::factory()->for(Invoice::factory()->create(['discount' => null, 'transitory' => false]))->create([
            'started_at' => $mondayThisWeek->copy()->addHours(9),
            'finished_at' => $mondayThisWeek->copy()->addHours(14),
            'pause_duration' => 0,
        ]);

        $widget = new StatsOverview();
        [$revenue, $hours] = (new ReflectionMethod($widget, 'getData'))->invoke($widget);

        $this->assertSame(5.0, end($hours));
        $this->assertGreaterThan(0, end($revenue));
    }

    #[Test]
    public function it_applies_discounts_and_excludes_transitory_invoices(): void
    {
        $mondayThisWeek = now()->startOfWeek(Carbon::MONDAY);
        $attributes = [
            'started_at' => $mondayThisWeek->copy()->addHours(9),
            'finished_at' => $mondayThisWeek->copy()->addHours(14),
            'pause_duration' => 0,
        ];
        $discounted = Invoice::factory()->create([
            'pricing_unit' => PricingUnit::Hour,
            'price' => 100,
            'discount' => 50,
            'transitory' => false,
        ]);
        $transitory = Invoice::factory()->create([
            'pricing_unit' => PricingUnit::Hour,
            'price' => 100,
            'discount' => null,
            'transitory' => true,
        ]);
        Position::factory()->for($discounted)->create($attributes);
        Position::factory()->for($transitory)->create($attributes);

        $widget = new StatsOverview();
        [$revenue, $hours] = (new ReflectionMethod($widget, 'getData'))->invoke($widget);

        $this->assertSame(450.0, end($revenue));
        $this->assertSame(5.0, end($hours));
    }
}
