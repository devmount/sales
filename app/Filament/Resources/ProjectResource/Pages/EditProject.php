<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Models\Project;
use App\Services\ProjectService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate')
                ->label(__('generateQuote'))
                ->icon('tabler-file-plus')
                ->action(function (Project $record) {
                    ProjectService::generateDocument($record);
                    Notification::make()->title(__('quoteGenerated'))->success()->send();
                }),
            Action::make('pdf')
                ->label(__('downloadFiletype', ['type' => 'pdf']))
                ->icon('tabler-file-type-pdf')
                ->disabled(fn(Project $record) => !$record->documents()->exists())
                ->action(function (Project $record) {
                    $document = $record->documents()->latest()->firstOrFail();
                    return Storage::disk($document->disk)->download($document->path, $document->filename);
                }),
            DeleteAction::make()->icon('tabler-trash'),
        ];
    }
}
