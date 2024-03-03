<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
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
        $activeCount = Invoice::whereNull('invoiced_at')->whereNull('paid_at')->count();
        $waitingCount = Invoice::whereNotNull('invoiced_at')->whereNull('paid_at')->count();
        $finishedCount = Invoice::whereNotNull('invoiced_at')->whereNotNull('paid_at')->count();

        return [
            'active' => Tab::make()
                ->label(__('inProgress'))
                ->badge($activeCount)
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('invoiced_at')->whereNull('paid_at')),
            'waiting' => Tab::make()
                ->label(__('waitingForPayment'))
                ->badge($waitingCount)
                ->badgeColor($waitingCount > 0 ? 'warning' : 'gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('invoiced_at')->whereNull('paid_at')),
            'finished' => Tab::make()
                ->label(__('finished'))
                ->badge($finishedCount)
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('invoiced_at')->whereNotNull('paid_at')),
            'all' => Tab::make()
                ->label(__('all')),
        ];
    }
}
