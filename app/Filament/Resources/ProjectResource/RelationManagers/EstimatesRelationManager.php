<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Models\Estimate;
use Carbon\Carbon;
use Filament\Forms\Components;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions;
use Filament\Tables\Columns;
use Filament\Tables\Table;

class EstimatesRelationManager extends RelationManager
{
    protected static string $relationship = 'estimates';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Components\TextInput::make('title')
                    ->label(__('title'))
                    ->columnSpanFull()
                    ->required(),
                Components\Textarea::make('description')
                    ->label(__('description'))
                    ->autosize()
                    ->columnSpanFull()
                    ->maxLength(65535),
                Components\TextInput::make('amount')
                    ->label(trans_choice('estimate', 1))
                    ->numeric()
                    ->step(0.1)
                    ->minValue(0.1)
                    ->suffix('h')
                    ->suffixIcon('tabler-clock-exclamation'),
                Components\TextInput::make('weight')
                    ->label(__('weight'))
                    ->numeric()
                    ->step(1)
                    ->helperText(__('definesEstimateSorting'))
                    ->suffixIcon('tabler-arrows-sort'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitle(trans_choice('estimate', 1))
            ->heading(trans_choice('estimate', 2))
            ->defaultSort('weight', 'asc')
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
                Actions\CreateAction::make()->icon('tabler-plus'),
            ])
            ->actions([
                Actions\EditAction::make()->icon('tabler-edit')->label(''),
                Actions\ReplicateAction::make()->icon('tabler-copy')->label(''),
                Actions\DeleteAction::make()->icon('tabler-trash')->label(''),
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
        return trans_choice('estimate', 1);
    }
}
