<?php

namespace App\Filament\Resources\InvoiceResource\Widgets;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use Filament\Actions\Action;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ActiveInvoices extends TableWidget
{
    // protected int | string | array $columnSpan = '2';
    public ?Invoice $record = null;

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('invoicesInProgress'))
            ->query(Invoice::active()->whereNot('id', $this->record?->id))
            ->paginated(false)
            ->defaultSort('created_at', 'desc')
            ->columns([
                ColorColumn::make('project.client.color')
                    ->label(''),
                TextColumn::make('title')
                    ->label(__('title')),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label('')
                    ->icon('tabler-edit')
                    ->url(fn (Invoice $i): string => InvoiceResource::getUrl('edit', ['record' => $i])),
            ]);
    }
}
