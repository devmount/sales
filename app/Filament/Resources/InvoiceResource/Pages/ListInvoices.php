<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->icon('tabler-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'active' => Tab::make()
                ->label(__('inProgress'))
                ->badge(Invoice::query()->whereNull('invoiced_at')->whereNull('paid_at')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('invoiced_at')->whereNull('paid_at')),
            'finished' => Tab::make()
                ->label(__('waitingForPayment'))
                ->badge(Invoice::query()->whereNotNull('invoiced_at')->whereNull('paid_at')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('invoiced_at')->whereNull('paid_at')),
            'aborted' => Tab::make()
                ->label(__('finished'))
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('invoiced_at')->whereNotNull('paid_at')),
            'all' => Tab::make()
                ->label(__('all')),
        ];
    }
}
