<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EstimateResource\Pages;
use App\Models\Estimate;
use Filament\Forms\Components;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions;
use Filament\Tables\Columns;
use Filament\Tables\Table;
use Filament\Tables\Filters;

class EstimateResource extends Resource
{
    protected static ?string $model = Estimate::class;
    protected static ?string $navigationIcon = 'tabler-clock-code';
    protected static ?int $navigationSort = 25;

    public static function form(Form $form): Form
    {
        return $form->schema(self::formFields());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultGroup('project.title')
            ->columns([
                Columns\ColorColumn::make('project.client.color')
                    ->label('')
                    ->tooltip(fn (Estimate $record): ?string => $record->project?->client?->name),
                Columns\TextColumn::make('title')
                    ->label(__('title'))
                    ->searchable()
                    ->sortable()
                    ->description(fn (Estimate $record): string => substr($record->description, 0, 75) . '...'),
                Columns\TextColumn::make('amount')
                    ->label(trans_choice('hour', 2))
                    ->numeric()
                    ->sortable(),
                Columns\TextColumn::make('weight')
                    ->label(__('weight'))
                    ->numeric()
                    ->sortable(),
                Columns\TextColumn::make('created_at')
                    ->label(__('createdAt'))
                    ->datetime('j. F Y, H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Columns\TextColumn::make('updated_at')
                    ->label(__('updatedAt'))
                    ->datetime('j. F Y, H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filters\SelectFilter::make('project')
                    ->label(trans_choice('project', 1))
                    ->relationship('project', 'title')
            ])
            ->actions(
                Actions\ActionGroup::make([
                    Actions\EditAction::make()->icon('tabler-edit')->slideOver()->modalWidth(MaxWidth::Large),
                    Actions\ReplicateAction::make()
                        ->icon('tabler-copy')
                        ->form(self::formFields())
                        ->slideOver()
                        ->modalWidth(MaxWidth::Large),
                    Actions\DeleteAction::make()->icon('tabler-trash')->requiresConfirmation(),
                ])
                ->icon('tabler-dots-vertical')
            )
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()->icon('tabler-trash'),
                ])
                ->icon('tabler-dots-vertical'),
            ])
            ->emptyStateActions([
                Actions\CreateAction::make()->icon('tabler-plus')->slideOver()->modalWidth(MaxWidth::Large),
            ])
            ->emptyStateIcon('tabler-ban')
            ->deferLoading();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEstimates::route('/'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('coreData');
    }

    public static function getNavigationLabel(): string
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

    public static function formFields(): array
    {
        return [
            Components\Grid::make()->columns(2)->schema([
                Components\Select::make('project_id')
                    ->label(trans_choice('project', 1))
                    ->relationship('project', 'title')
                    ->searchable()
                    ->suffixIcon('tabler-package')
                    ->columnSpanFull()
                    ->required(),
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
                    ->label(__('estimatedHours'))
                    ->numeric()
                    ->step(0.1)
                    ->minValue(0.1)
                    ->suffix('h')
                    ->suffixIcon('tabler-clock-exclamation')
                    ->required(),
                Components\TextInput::make('weight')
                    ->label(__('weight'))
                    ->numeric()
                    ->step(1)
                    ->helperText(__('definesEstimateSorting'))
                    ->suffixIcon('tabler-arrows-sort'),
            ])
        ];
    }
}
