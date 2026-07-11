<?php

namespace Tests\Feature;

use App\Filament\Widgets\GiftSubjectDistributionChart;
use App\Models\Gift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class GiftSubjectDistributionChartTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renders_successfully(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(GiftSubjectDistributionChart::class)->assertSuccessful();
    }

    #[Test]
    public function it_distributes_gift_amounts_by_subject_for_the_selected_year(): void
    {
        $year = now()->year - 1;
        Gift::factory()->create(['received_at' => "$year-03-01", 'subject' => 'Birthday', 'amount' => 300]);
        Gift::factory()->create(['received_at' => "$year-09-01", 'subject' => 'Anniversary', 'amount' => 100]);
        // Gift outside the selected year should not be counted
        Gift::factory()->create(['received_at' => now()->year - 5 . '-01-01', 'subject' => 'Old', 'amount' => 1000]);

        $widget = new GiftSubjectDistributionChart();
        $widget->filter = (string) $year;
        $data = (new ReflectionMethod($widget, 'getData'))->invoke($widget);

        $this->assertSame([300.0, 100.0], $data['datasets'][0]['data']);
        $this->assertStringContainsString('(75%) Birthday', $data['labels'][0]);
        $this->assertStringContainsString('(25%) Anniversary', $data['labels'][1]);
    }

    #[Test]
    public function it_sums_all_gifts_regardless_of_year_when_filter_is_all(): void
    {
        Gift::factory()->create(['received_at' => '2020-01-01', 'subject' => 'Old gift', 'amount' => 50]);
        Gift::factory()->create(['received_at' => now(), 'subject' => 'Recent gift', 'amount' => 50]);

        $widget = new GiftSubjectDistributionChart();
        $widget->filter = 'all';
        $data = (new ReflectionMethod($widget, 'getData'))->invoke($widget);

        $this->assertSame([50.0, 50.0], $data['datasets'][0]['data']);
    }

    #[Test]
    public function it_groups_gifts_without_a_subject_under_not_available(): void
    {
        Gift::factory()->create(['received_at' => now(), 'subject' => null, 'amount' => 20]);

        $widget = new GiftSubjectDistributionChart();
        $widget->filter = 'all';
        $data = (new ReflectionMethod($widget, 'getData'))->invoke($widget);

        $this->assertStringContainsString(__('n/a'), $data['labels'][0]);
    }
}
