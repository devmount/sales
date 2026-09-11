<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\InvoiceResource\Widgets\ActiveInvoices;
use App\Filament\Resources\InvoiceResource\Widgets\ClientInvoices;
use App\Filament\Resources\PositionResource\Widgets\RecentPositionsChart;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    public function getFooterWidgetsColumns(): int|array
    {
        return 12;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate')
                ->label(__('generateInvoiceDocument'))
                ->icon('tabler-file-plus')
                ->action(function (Invoice $record) {
                    InvoiceService::generateDocuments($record);
                    Notification::make()->title(__('invoiceDocumentGenerated'))->success()->send();
                }),
            Action::make('pdf')
                ->label(__('downloadFiletype', ['type' => 'pdf']))
                ->icon('tabler-file-type-pdf')
                ->hidden(fn(Invoice $record) => !$record->documents()->exists())
                ->action(function (Invoice $record) {
                    $document = $record->documents()->latest()->firstOrFail();
                    return Storage::disk($document->disk)->download($document->path, $document->filename);
                }),
            Action::make('xml')
                ->label(__('downloadFiletype', ['type' => 'xml']))
                ->icon('tabler-file-type-xml')
                ->hidden(fn(Invoice $record) => !$record->documents()->whereNotNull('attachment_path')->exists())
                ->action(function (Invoice $record) {
                    $document = $record->documents()->whereNotNull('attachment_path')->latest()->firstOrFail();
                    return Storage::disk($document->disk)->download($document->attachment_path, $document->attachment_filename);
                }),
            DeleteAction::make()->icon('tabler-trash')->requiresConfirmation(),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            RecentPositionsChart::make(['columnSpan' => 12]),
            ActiveInvoices::class,
            ClientInvoices::class,
        ];
    }

}
