<?php

namespace Tests\Feature;

use App\Filament\Widgets\SumGiftsChart;
use App\Models\Gift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class SumGiftsChartTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renders_successfully(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(SumGiftsChart::class)->assertSuccessful();
    }

    #[Test]
    public function it_sums_gift_amounts_per_year(): void
    {
        $year = now()->year - 1;
        Gift::factory()->create(['received_at' => "$year-02-01", 'amount' => 100]);
        Gift::factory()->create(['received_at' => "$year-11-01", 'amount' => 50]);

        $widget = new SumGiftsChart();
        $widget->filter = 'y';
        $data = (new ReflectionMethod($widget, 'getData'))->invoke($widget);

        $yearIndex = array_search((string) $year, $data['labels'], true);

        $this->assertNotFalse($yearIndex);
        $this->assertSame(150.0, $data['datasets'][0]['data'][$yearIndex]);
    }

    #[Test]
    public function it_sums_gift_amounts_per_quarter(): void
    {
        $year = now()->year - 1;
        Gift::factory()->create(['received_at' => "$year-02-15", 'amount' => 40]);
        Gift::factory()->create(['received_at' => "$year-02-20", 'amount' => 10]);

        $widget = new SumGiftsChart();
        $widget->filter = 'q';
        $data = (new ReflectionMethod($widget, 'getData'))->invoke($widget);

        $quarterIndex = array_search("$year Q1", $data['labels'], true);

        $this->assertNotFalse($quarterIndex);
        $this->assertSame(50.0, $data['datasets'][0]['data'][$quarterIndex]);
    }
}
