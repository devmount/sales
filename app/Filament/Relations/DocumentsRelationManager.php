<?php

namespace App\Filament\Relations;

use App\Models\Document;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitle(fn(Document $record) => $record->filename)
            ->heading(trans_choice('document', 2))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('filename')
                    ->label(__('filename')),
                TextColumn::make('size')
                    ->label(__('size'))
                    ->formatStateUsing(fn(int $state): string => Number::fileSize($state))
                    ->fontFamily(FontFamily::Mono),
                TextColumn::make('created_at')
                    ->label(__('createdAt'))
                    ->dateTime('j. F Y, H:i:s'),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('')
                    ->icon('tabler-file-type-pdf')
                    ->action(fn(Document $record) => Storage::disk($record->disk)->download($record->path, $record->filename)),
                Action::make('downloadAttachment')
                    ->label('')
                    ->icon('tabler-file-type-xml')
                    ->hidden(fn(Document $record) => !$record->attachment_path)
                    ->action(fn(Document $record) => Storage::disk($record->disk)->download($record->attachment_path, $record->attachment_filename)),
                DeleteAction::make()
                    ->icon('tabler-trash')
                    ->label('')
                    ->requiresConfirmation(),
            ])
            ->paginated(false);
    }

    public static function getModelLabel(): string
    {
        return trans_choice('document', 1);
    }
}
