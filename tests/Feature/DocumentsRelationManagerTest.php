<?php

namespace Tests\Feature;

use App\Filament\Relations\DocumentsRelationManager;
use App\Filament\Resources\InvoiceResource\Pages\EditInvoice;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DocumentsRelationManagerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_document_and_refreshes_the_page(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $invoice = Invoice::factory()->create();
        $document = Document::factory()->for($invoice, 'documentable')->create();

        Livewire::test(DocumentsRelationManager::class, [
            'ownerRecord' => $invoice,
            'pageClass' => EditInvoice::class,
        ])
            ->callAction(TestAction::make(DeleteAction::class)->table($document))
            ->assertDispatched('refresh-page');

        $this->assertModelMissing($document);
    }
}
