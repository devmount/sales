<?php

namespace App\Filament\Resources\PositionResource\Pages;

use App\Filament\Resources\PositionResource;
use App\Filament\Resources\PositionResource\Widgets\RecentPositionsChart;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePositions extends ManageRecords
{
    protected static string $resource = PositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->icon('tabler-plus'),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            RecentPositionsChart::class,
        ];
    }
}
