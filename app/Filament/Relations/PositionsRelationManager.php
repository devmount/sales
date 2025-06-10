<?php

namespace App\Filament\Relations;

use App\Filament\Resources\PositionResource;
use App\Models\Position;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions;
use Filament\Tables\Columns;
use Filament\Tables\Table;

class PositionsRelationManager extends RelationManager
{
    protected static string $relationship = 'positions';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitle(trans_choice('position', 1))
            ->heading(trans_choice('position', 2))
            ->defaultSort('started_at', 'asc')
            ->columns([
                Columns\TextColumn::make('description')
                    ->label(__('description'))
                    ->copyable()
                    ->formatStateUsing(fn (string $state): string => nl2br($state))
                    ->html(),
                Columns\TextColumn::make('amount')
                    ->label(trans_choice('hour', 2))
                    ->state(fn (Position $record): float => $record->duration)
                    ->weight(FontWeight::ExtraBold)
                    ->description(fn (Position $record): string => $record->time_range),
                Columns\ToggleColumn::make('remote')
                    ->label(__('remote')),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Actions\CreateAction::make()
                    ->icon('tabler-plus')
                    ->form(PositionResource::formFields())
                    ->slideOver()
                    ->modalWidth(MaxWidth::TwoExtraLarge),
            ])
            ->actions([
                Actions\EditAction::make()
                    ->icon('tabler-edit')
                    ->label('')
                    ->form(PositionResource::formFields())
                    ->slideOver()
                    ->modalWidth(MaxWidth::TwoExtraLarge),
                Actions\ReplicateAction::make()
                    ->icon('tabler-copy')
                    ->label('')
                    ->form(PositionResource::formFields())
                    ->slideOver()
                    ->modalWidth(MaxWidth::TwoExtraLarge),
                Actions\DeleteAction::make()
                    ->icon('tabler-trash')
                    ->label('')
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()->icon('tabler-trash'),
                ])
                ->icon('tabler-dots-vertical'),
            ])
            ->emptyStateActions([
                Actions\CreateAction::make()->icon('tabler-plus'),
            ])
            ->paginated(false);
    }

    public static function getModelLabel(): string
    {
        return trans_choice('position', 1);
    }
}
