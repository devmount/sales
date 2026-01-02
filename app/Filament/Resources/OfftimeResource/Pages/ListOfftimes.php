<?php

namespace App\Filament\Resources\OfftimeResource\Pages;

use App\Filament\Resources\OfftimeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListOfftimes extends ListRecords
{
    protected static string $resource = OfftimeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('tabler-plus')
                ->schema(OfftimeResource::formFields(6, false))
                ->slideOver()
                ->modalWidth(Width::Large),
        ];
    }
}
