<?php

namespace App\Filament\Resources\OfftimeResource\Pages;

use App\Filament\Resources\OfftimeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;

class ListOfftimes extends ListRecords
{
    protected static string $resource = OfftimeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->icon('tabler-plus')->slideOver()->modalWidth(MaxWidth::Large),
        ];
    }
}
