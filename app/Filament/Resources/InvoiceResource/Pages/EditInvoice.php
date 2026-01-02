<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\InvoiceResource\Widgets\ActiveInvoices;
use App\Filament\Resources\PositionResource\Widgets\RecentPositionsChart;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')
                ->label(__('downloadFiletype', ['type' => 'pdf']))
                ->icon('tabler-file-type-pdf')
                ->action(function (Invoice $record) {
                    Storage::delete(Storage::allFiles());
                    $file = InvoiceService::generatePdf($record);
                    return response()->download(Storage::path($file));
                }),
            Action::make('xml')
                ->label(__('downloadFiletype', ['type' => 'xml']))
                ->icon('tabler-file-type-xml')
                ->action(function (Invoice $record) {
                    Storage::delete(Storage::allFiles());
                    $file = InvoiceService::generateEn16931Xml($record);
                    return response()->download(Storage::path($file));
                }),
            DeleteAction::make()->icon('tabler-trash')->requiresConfirmation(),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            RecentPositionsChart::class,
            ActiveInvoices::class,
        ];
    }

}
