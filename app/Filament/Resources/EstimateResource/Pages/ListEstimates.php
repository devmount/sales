<?php

namespace App\Filament\Resources\EstimateResource\Pages;

use App\Filament\Resources\EstimateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;

class ListEstimates extends ListRecords
{
    protected static string $resource = EstimateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->icon('tabler-plus')->slideOver()->modalWidth(MaxWidth::Large),
        ];
    }
}
