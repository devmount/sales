<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Enums\ExpenseCategory;
use App\Filament\Resources\ExpenseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;

class ListExpenses extends ListRecords
{
    protected static string $resource = ExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('tabler-plus')
                ->schema(ExpenseResource::formFields(6, false))
                ->slideOver()
                ->modalWidth(Width::Large),
        ];
    }

    public function getTabs(): array
    {
        return [
            'deliverables' => Tab::make()
                ->label(__('deliverables'))
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('category', ExpenseCategory::deliverableCategories())),
            'tax' => Tab::make()
                ->label(__('taxes'))
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('category', ExpenseCategory::taxCategories())),
            'all' => Tab::make()
                ->label(__('all')),
        ];
    }
}
