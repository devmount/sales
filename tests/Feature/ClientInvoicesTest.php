<?php

namespace Tests\Feature;

use App\Filament\Resources\InvoiceResource\Widgets\ClientInvoices;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClientInvoicesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renders_successfully(): void
    {
        $this->actingAs(User::factory()->create());
        $invoice = Invoice::factory()->create();

        Livewire::test(ClientInvoices::class, ['record' => $invoice])->assertSuccessful();
    }

    #[Test]
    public function it_lists_other_invoices_of_the_same_client_only(): void
    {
        $this->actingAs(User::factory()->create());
        $client = Client::factory()->create();
        $projectA = Project::factory()->for($client)->create();
        $projectB = Project::factory()->for($client)->create();

        $current = Invoice::factory()->for($projectA)->create();
        $sameClientOtherProject = Invoice::factory()->for($projectB)->create();
        $otherClientsInvoice = Invoice::factory()->create();

        Livewire::test(ClientInvoices::class, ['record' => $current])
            ->assertCanSeeTableRecords([$sameClientOtherProject])
            ->assertCanNotSeeTableRecords([$current, $otherClientsInvoice]);
    }
}
