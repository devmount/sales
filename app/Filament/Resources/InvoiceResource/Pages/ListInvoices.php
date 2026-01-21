<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('tabler-plus')
                ->schema(InvoiceResource::formFields(6, false))
                ->slideOver()
                ->modalWidth(Width::ExtraLarge),
        ];
    }

    public function getTabs(): array
    {
        $active = Invoice::active();
        $activeNet = $active->get()->reduce(fn($p, $c) => $p + $c->net, 0);
        $activeCount = $active->count();
        $waiting = Invoice::waiting();
        $waitingCount = $waiting->count();
        $waitingNet = $waiting->get()->reduce(fn($p, $c) => $p + $c->net, 0);
        $finishedCount = Invoice::finished()->count();

        return [
            'active' => Tab::make()
                ->label(__('inProgress', ['net' => Number::currency($activeNet, 'eur') ]))
                ->badge($activeCount)
                ->modifyQueryUsing(fn (Builder $query) => $query->active()->orderBy('updated_at', 'desc')),
            'waiting' => Tab::make()
                ->label(__('waitingForPayment', ['net' => Number::currency($waitingNet, 'eur') ]))
                ->badge($waitingCount)
                ->badgeColor($waitingCount > 0 ? 'warning' : 'gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->waiting()->orderBy('invoiced_at', 'desc')),
            'finished' => Tab::make()
                ->label(__('finished'))
                ->badge($finishedCount)
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->finished()->orderBy('paid_at', 'desc')),
            'all' => Tab::make()
                ->label(__('all')),
        ];
    }
}
