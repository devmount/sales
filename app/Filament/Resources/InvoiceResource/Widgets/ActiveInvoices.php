<?php

namespace App\Filament\Resources\InvoiceResource\Widgets;

use App\Models\Invoice;
use App\Filament\Resources\InvoiceResource;
use Filament\Tables\Columns;
use Filament\Tables\Actions;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ActiveInvoices extends BaseWidget
{
    // protected int | string | array $columnSpan = '2';
    public ?Invoice $record = null;

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('invoicesInProgress'))
            ->query(Invoice::query()->whereNull('invoiced_at')->whereNull('paid_at')->whereNot('id', $this->record?->id))
            ->paginated(false)
            ->defaultSort('created_at', 'desc')
            ->columns([
                Columns\ColorColumn::make('project.client.color')
                    ->label(''),
                Columns\TextColumn::make('title')
                    ->label(__('title')),
            ])
            ->actions([
                Actions\Action::make('edit')
                    ->label('')
                    ->icon('tabler-edit')
                    ->url(fn (Invoice $i): string => InvoiceResource::getUrl('edit', ['record' => $i])),
            ]);
    }
}
