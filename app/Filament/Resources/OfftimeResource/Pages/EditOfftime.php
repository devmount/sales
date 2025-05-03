<?php

namespace App\Filament\Resources\OfftimeResource\Pages;

use App\Filament\Resources\OfftimeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOfftime extends EditRecord
{
    protected static string $resource = OfftimeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
