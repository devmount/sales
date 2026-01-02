<?php

namespace App\Filament\Resources\PositionResource\Pages;

use App\Filament\Resources\PositionResource;
use App\Filament\Resources\PositionResource\Widgets\RecentPositionsChart;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListPositions extends ListRecords
{
    protected static string $resource = PositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('tabler-plus')
                ->schema(PositionResource::formFields(6, false))
                ->slideOver()
                ->modalWidth(Width::TwoExtraLarge),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            RecentPositionsChart::class,
        ];
    }
}
