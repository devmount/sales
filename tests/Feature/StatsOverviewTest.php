<?php

namespace Tests\Feature;

use App\Filament\Widgets\StatsOverview;
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

        Position::factory()->create([
            'started_at' => $mondayThisWeek->copy()->addHours(9),
            'finished_at' => $mondayThisWeek->copy()->addHours(14),
            'pause_duration' => 0,
        ]);

        $widget = new StatsOverview();
        [$revenue, $hours] = (new ReflectionMethod($widget, 'getData'))->invoke($widget);

        $this->assertSame(5.0, end($hours));
        $this->assertGreaterThan(0, end($revenue));
    }
}
