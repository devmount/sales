<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Models\Project;
use App\Services\ProjectService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download')
                ->label(__('quote'))
                ->icon('tabler-file-type-pdf')
                ->action(function (Project $record) {
                    Storage::delete(Storage::allFiles());
                    $file = ProjectService::generateQuotePdf($record);
                    return response()->download(Storage::path($file));
                }),
            Actions\DeleteAction::make()
                ->icon('tabler-trash'),
        ];
    }
}
