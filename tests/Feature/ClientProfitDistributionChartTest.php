<?php

namespace Tests\Feature;

use App\Enums\PricingUnit;
use App\Filament\Widgets\ClientProfitDistributionChart;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Position;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class ClientProfitDistributionChartTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renders_successfully(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ClientProfitDistributionChart::class)->assertSuccessful();
    }

    #[Test]
    public function it_distributes_net_profit_by_client_for_the_selected_year(): void
    {
        $year = now()->year - 1;
        $clientA = Client::factory()->create(['name' => 'Client A']);
        $clientB = Client::factory()->create(['name' => 'Client B']);

        $invoiceA = Invoice::factory()
            ->for(Project::factory()->for($clientA))
            ->create(['paid_at' => "$year-06-01", 'transitory' => false, 'pricing_unit' => PricingUnit::Hour, 'price' => 100, 'discount' => null]);
        Position::factory()->for($invoiceA)->withDuration(6)->create(['pause_duration' => 0]);

        $invoiceB = Invoice::factory()
            ->for(Project::factory()->for($clientB))
            ->create(['paid_at' => "$year-06-01", 'transitory' => false, 'pricing_unit' => PricingUnit::Hour, 'price' => 100, 'discount' => null]);
        Position::factory()->for($invoiceB)->withDuration(2)->create(['pause_duration' => 0]);

        $widget = new ClientProfitDistributionChart();
        $widget->filter = (string) $year;
        $data = (new ReflectionMethod($widget, 'getData'))->invoke($widget);

        // Client A: 600 net (75%), Client B: 200 net (25%)
        $this->assertSame([600.0, 200.0], $data['datasets'][0]['data']);
        $this->assertStringContainsString('(75%) Client A', $data['labels'][0]);
        $this->assertStringContainsString('(25%) Client B', $data['labels'][1]);
    }

    #[Test]
    public function it_shows_a_placeholder_when_there_is_no_data_for_the_selected_year(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ClientProfitDistributionChart::class)
            ->assertSuccessful()
            ->assertSee(__('noDataAvailable'));
    }
}
