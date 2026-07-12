<?php

namespace Tests\Feature;

use App\Filament\Resources\InvoiceResource\Widgets\ActiveInvoices;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActiveInvoicesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renders_successfully(): void
    {
        $this->actingAs(User::factory()->create());
        $invoice = Invoice::factory()->create();

        Livewire::test(ActiveInvoices::class, ['record' => $invoice])->assertSuccessful();
    }

    #[Test]
    public function it_lists_other_active_invoices_excluding_the_current_record(): void
    {
        $this->actingAs(User::factory()->create());
        $current = Invoice::factory()->active()->create();
        $otherActive = Invoice::factory()->active()->create();
        $finished = Invoice::factory()->finished()->create();

        Livewire::test(ActiveInvoices::class, ['record' => $current])
            ->assertCanSeeTableRecords([$otherActive])
            ->assertCanNotSeeTableRecords([$current, $finished]);
    }
}
