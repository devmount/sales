<?php

namespace App\Filament\Resources\GiftResource\Pages;

use App\Filament\Resources\GiftResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListGifts extends ListRecords
{
    protected static string $resource = GiftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('tabler-plus')
                ->schema(GiftResource::formFields(6, false))
                ->slideOver()
                ->modalWidth(Width::Large),
        ];
    }
}
