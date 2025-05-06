<?php

namespace App\Filament\Resources;

use App\Enums\OfftimeCategory;
use App\Filament\Resources\OfftimeResource\Pages;
use App\Models\Offtime;
use Filament\Forms\Components;
use Filament\Forms\Form;
use Filament\Tables\Columns;
use Filament\Tables\Actions;
use Filament\Resources\Resource;
use Filament\Tables\Filters;
use Filament\Tables\Table;

class OfftimeResource extends Resource
{
    protected static ?string $model = Offtime::class;
    protected static ?string $navigationIcon = 'tabler-beach';
    protected static ?int $navigationSort = 60;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Components\Section::make()
                    ->columns(12)
                    ->schema([
                        Components\DatePicker::make('start')
                            ->label(__('startAt'))
                            ->columnSpan(3)
                            ->weekStartsOnMonday()
                            ->suffixIcon('tabler-calendar-play')
                            ->required(),
                        Components\DatePicker::make('end')
                            ->label(__('finished'))
                            ->columnSpan(3)
                            ->weekStartsOnMonday()
                            ->suffixIcon('tabler-calendar-pause'),
                        Components\Select::make('category')
                            ->label(__('pricingUnit'))
                            ->columnSpan(3)
                            ->options(OfftimeCategory::class)
                            ->suffixIcon('tabler-category')
                            ->required(),
                        Components\TextInput::make('description')
                            ->label(__('description'))
                            ->columnSpan(6)
                            ->suffixIcon('tabler-text'),
                    ])
            ]);
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
                    Actions\EditAction::make()->icon('tabler-edit'),
                    // Actions\ReplicateAction::make()->icon('tabler-copy'),
                    // Actions\DeleteAction::make()->icon('tabler-trash'),
                ])
                ->icon('tabler-dots-vertical')
            )
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Actions\CreateAction::make()->icon('tabler-plus'),
            ])
            ->emptyStateIcon('tabler-ban')
            ->defaultSort('start', 'desc')
            ->deferLoading();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOfftimes::route('/'),
            'create' => Pages\CreateOfftime::route('/create'),
            'edit' => Pages\EditOfftime::route('/{record}/edit'),
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
}
