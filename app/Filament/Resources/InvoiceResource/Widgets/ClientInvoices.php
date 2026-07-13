<?php

namespace App\Filament\Resources\InvoiceResource\Widgets;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use Filament\Actions\Action;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ClientInvoices extends TableWidget
{
    public ?Invoice $record = null;

    protected int|string|array $columnSpan = 6;

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('otherInvoices'))
            ->query(
                Invoice::whereHas('project', fn($query) => $query->where('client_id', $this->record?->project->client_id))
                    ->whereNot('id', $this->record?->id),
            )
            ->paginated([8])
            ->defaultSort('created_at', 'desc')
            ->columns([
                ColorColumn::make('project.client.color')
                    ->label(''),
                TextColumn::make('title')
                    ->label(__('title')),
                TextColumn::make('invoiced_at')
                    ->label(__('invoiceDate'))
                    ->date('j. F Y'),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label('')
                    ->icon('tabler-edit')
                    ->url(fn(Invoice $i): string => InvoiceResource::getUrl('edit', ['record' => $i])),
            ]);
    }
}
