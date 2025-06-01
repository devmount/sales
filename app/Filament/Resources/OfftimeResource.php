<?php

namespace App\Filament\Resources;

use App\Enums\OfftimeCategory;
use App\Filament\Resources\OfftimeResource\Pages;
use App\Models\Offtime;
use Filament\Forms\Components;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions;
use Filament\Tables\Columns;
use Filament\Tables\Filters;
use Filament\Tables\Table;

class OfftimeResource extends Resource
{
    protected static ?string $model = Offtime::class;
    protected static ?string $navigationIcon = 'tabler-beach';
    protected static ?int $navigationSort = 60;

    public static function form(Form $form): Form
    {
        return $form->schema(self::formFields());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Columns\TextColumn::make('start')
                    ->label(__('startAt'))
                    ->date('j. F Y')
                    ->sortable(),
                Columns\TextColumn::make('days')
                    ->label(trans_choice('day', 2))
                    ->state(fn (Offtime $record): string => $record->days_count ?? '')
                    ->sortable(),
                Columns\TextColumn::make('category')
                    ->label(__('category'))
                    ->badge()
                    ->sortable(),
                Columns\TextColumn::make('description')
                    ->label(__('description'))
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                Filters\SelectFilter::make('category')
                    ->options(OfftimeCategory::options())
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
                ]),
            ])
            ->emptyStateActions([
                Actions\CreateAction::make()->icon('tabler-plus')->slideOver()->modalWidth(MaxWidth::Large),
            ])
            ->emptyStateIcon('tabler-ban')
            ->defaultSort('start', 'desc')
            ->deferLoading();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOfftimes::route('/'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('coreData');
    }

    public static function getNavigationLabel(): string
    {
        return trans_choice('offtime', 2);
    }

    public static function getModelLabel(): string
    {
        return trans_choice('offtime', 1);
    }

    public static function getPluralModelLabel(): string
    {
        return trans_choice('offtime', 2);
    }

    public static function formFields(): array
    {
        return [
            Components\Grid::make()->columns(2)->schema([
                Components\DatePicker::make('start')
                    ->label(__('startAt'))
                    ->weekStartsOnMonday()
                    ->suffixIcon('tabler-calendar-dot')
                    ->required(),
                Components\DatePicker::make('end')
                    ->label(__('finished'))
                    ->weekStartsOnMonday()
                    ->suffixIcon('tabler-calendar-pause'),
                Components\Select::make('category')
                    ->label(__('category'))
                    ->columnSpanFull()
                    ->options(OfftimeCategory::class)
                    ->suffixIcon('tabler-category')
                    ->required(),
                Components\Textarea::make('description')
                    ->label(__('description'))
                    ->columnSpanFull(),
            ])
        ];
    }
}
