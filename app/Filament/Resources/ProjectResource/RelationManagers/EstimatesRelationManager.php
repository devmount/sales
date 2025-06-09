<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Filament\Resources\EstimateResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions;
use Filament\Tables\Columns;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class EstimatesRelationManager extends RelationManager
{
    protected static string $relationship = 'estimates';

    // public function form(Form $form): Form
    // {
    //     return $form->schema([
    //         Components\TextInput::make('title')
    //             ->label(__('title'))
    //             ->required(),
    //         Components\TextInput::make('amount')
    //             ->label(__('estimatedHours'))
    //             ->numeric()
    //             ->step(0.1)
    //             ->minValue(0.1)
    //             ->suffix('h')
    //             ->suffixIcon('tabler-clock-exclamation')
    //             ->required(),
    //         Components\Textarea::make('description')
    //             ->label(__('description'))
    //             ->autosize()
    //             ->columnSpanFull()
    //             ->maxLength(65535),
    //     ]);
    // }

    public function table(Table $table): Table
    {
        return $table
            ->heading(trans_choice('estimate', 2))
            ->defaultSort('weight', 'asc')
            ->reorderable('weight')
            ->columns([
                Columns\TextColumn::make('title')
                    ->label(__('title')),
                Columns\TextColumn::make('description')
                    ->label(__('description'))
                    ->copyable()
                    ->formatStateUsing(fn (string $state): string => nl2br($state))
                    ->html(),
                Columns\TextColumn::make('amount')
                    ->label(trans_choice('hour', 2))
                    ->numeric()
                    ->weight(FontWeight::ExtraBold)
                    ->summarize(Columns\Summarizers\Sum::make()),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Actions\CreateAction::make()
                    ->icon('tabler-plus')
                    ->form(EstimateResource::formFields())
                    ->slideOver()
                    ->modalWidth(MaxWidth::Large),
            ])
            ->actions([
                Actions\EditAction::make()
                    ->icon('tabler-edit')
                    ->label('')
                    ->form(EstimateResource::formFields())
                    ->slideOver()
                    ->modalWidth(MaxWidth::Large),
                Actions\ReplicateAction::make()
                    ->icon('tabler-copy')
                    ->label('')
                    ->form(EstimateResource::formFields())
                    ->slideOver()
                    ->modalWidth(MaxWidth::Large),
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

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return trans_choice('estimate', 2);
    }

    public static function getModelLabel(): string
    {
        return trans_choice('estimate', 1);
    }

    public static function getPluralModelLabel(): string
    {
        return trans_choice('estimate', 2);
    }
}
