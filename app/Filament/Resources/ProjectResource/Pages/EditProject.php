<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Models\Project;
use App\Filament\Resources\ProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download')
                ->label(__('quote'))
                ->icon('tabler-file-type-pdf')
                ->url(fn (Project $record): string => static::$resource::getUrl('download', ['record' => $record]))
                ->openUrlInNewTab(),
            Actions\DeleteAction::make()
                ->icon('tabler-trash'),
        ];
    }
}
