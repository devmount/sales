<?php

namespace Tests\Feature;

use App\Enums\PricingUnit;
use App\Filament\Widgets\ClientHoursChart;
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

class ClientHoursChartTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renders_successfully(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ClientHoursChart::class)->assertSuccessful();
    }

    #[Test]
    public function it_groups_worked_hours_per_client_per_year(): void
    {
        $year = now()->year - 1;
        $clientA = Client::factory()->create(['name' => 'Client A', 'color' => '#111111']);
        $clientB = Client::factory()->create(['name' => 'Client B', 'color' => '#222222']);

        $invoiceA = Invoice::factory()
            ->for(Project::factory()->for($clientA))
            ->create(['paid_at' => "$year-06-01", 'transitory' => false, 'pricing_unit' => PricingUnit::Hour, 'price' => 100, 'discount' => null]);
        Position::factory()->for($invoiceA)->withDuration(5)->create(['pause_duration' => 0]);

        $invoiceB = Invoice::factory()
            ->for(Project::factory()->for($clientB))
            ->create(['paid_at' => "$year-06-01", 'transitory' => false, 'pricing_unit' => PricingUnit::Hour, 'price' => 50, 'discount' => null]);
        Position::factory()->for($invoiceB)->withDuration(3)->create(['pause_duration' => 0]);

        $widget = new ClientHoursChart();
        $data = (new ReflectionMethod($widget, 'getData'))->invoke($widget);

        $yearIndex = array_search((string) $year, $data['labels'], true);
        $datasetA = collect($data['datasets'])->firstWhere('label', 'Client A');
        $datasetB = collect($data['datasets'])->firstWhere('label', 'Client B');

        $this->assertNotFalse($yearIndex);
        $this->assertSame(5.0, $datasetA['data'][$yearIndex]);
        $this->assertSame(3.0, $datasetB['data'][$yearIndex]);
        $this->assertSame('#111111', $datasetA['borderColor']);
    }

    #[Test]
    public function it_excludes_transitory_and_unpaid_invoices(): void
    {
        $year = now()->year - 1;
        $client = Client::factory()->create();

        $unpaidInvoice = Invoice::factory()->for(Project::factory()->for($client))->create(['paid_at' => null]);
        Position::factory()->for($unpaidInvoice)->withDuration(4)->create(['pause_duration' => 0]);

        $transitoryInvoice = Invoice::factory()
            ->for(Project::factory()->for($client))
            ->create(['paid_at' => "$year-06-01", 'transitory' => true]);
        Position::factory()->for($transitoryInvoice)->withDuration(4)->create(['pause_duration' => 0]);

        $widget = new ClientHoursChart();
        $data = (new ReflectionMethod($widget, 'getData'))->invoke($widget);

        $this->assertSame([], $data['datasets']);
    }
}
