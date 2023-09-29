<?php

namespace App\Filament\Resources;

use App\Enums\ExpenseCategory;
use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Expense;
use Filament\Forms\Components;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Actions;
use Filament\Tables\Columns;
use Filament\Tables\Table;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;
    protected static ?string $navigationIcon = 'tabler-credit-card';
    protected static ?int $navigationSort = 40;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Components\Section::make()
                    ->columns(12)
                    ->schema([
                        Components\DatePicker::make('expended_at')
                            ->label(__('expendedAt'))
                            ->weekStartsOnMonday()
                            ->required()
                            ->default(now())
                            ->suffixIcon('tabler-calendar-dollar')
                            ->columnSpan(6),
                        Components\Select::make('category')
                            ->label(__('category'))
                            ->options(ExpenseCategory::class)
                            ->required()
                            ->default(ExpenseCategory::Good)
                            ->suffixIcon('tabler-tag')
                            ->columnSpan(6),
                        Components\TextInput::make('price')
                            ->label(__('price'))
                            ->numeric()
                            ->step(0.25)
                            ->suffixIcon('tabler-currency-euro')
                            ->required()
                            ->columnSpan(3),
                        Components\TextInput::make('quantity')
                            ->label(__('quantity'))
                            ->numeric()
                            ->step(1)
                            ->minValue(1)
                            ->default(1)
                            ->suffixIcon('tabler-stack')
                            ->required()
                            ->columnSpan(3),
                        Components\Toggle::make('taxable')
                            ->label(__('taxable'))
                            ->inline(false)
                            ->default(true)
                            ->columnSpan(1)
                            ->live(),
                        Components\TextInput::make('vat_rate')
                            ->label(__('vatRate'))
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0.01)
                            ->maxValue(1)
                            ->default(0.19)
                            ->suffixIcon('tabler-receipt-tax')
                            ->required()
                            ->columnSpan(5)
                            ->hidden(fn (Get $get): bool => !$get('taxable')),
                        Components\Textarea::make('description')
                            ->label(__('description'))
                            ->maxLength(65535)
                            ->columnSpan(12),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Columns\TextColumn::make('expended_at')
                    ->label(__('expendedAt'))
                    ->date('j. F Y')
                    ->sortable(),
                Columns\TextColumn::make('price')
                    ->label(__('gross'))
                    ->money('eur')
                    ->fontFamily(FontFamily::Mono)
                    ->alignment(Alignment::End)
                    ->sortable(),
                Columns\IconColumn::make('taxable')
                    ->label(__('taxable'))
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Columns\TextColumn::make('vat')
                    ->label(__('vat'))
                    ->money('eur')
                    ->fontFamily(FontFamily::Mono)
                    ->state(fn (Expense $record): float => $record->vat)
                    ->color(fn (string $state): string => $state == 0 ? 'gray' : 'normal')
                    // ->hidden(fn ($record): bool => in_array($record?->category, ExpenseCategory::taxCategories()))
                    ->sortable(),
                Columns\TextColumn::make('quantity')
                    ->label(__('quantity'))
                    ->numeric()
                    // ->hidden(fn ($record): bool => in_array($record?->category, ExpenseCategory::taxCategories()))
                    ->sortable(),
                Columns\TextColumn::make('category')
                    ->label(__('category'))
                    ->badge()
                    ->sortable(),
                Columns\TextColumn::make('description')
                    ->label(__('description')),
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
                //
            ])
            ->actions(
                Actions\ActionGroup::make([
                    Actions\EditAction::make()->icon('tabler-edit'),
                    Actions\ReplicateAction::make()->icon('tabler-copy'),
                    Actions\DeleteAction::make()->icon('tabler-trash'),
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
                Actions\CreateAction::make()->icon('tabler-plus'),
            ])
            ->emptyStateIcon('tabler-ban')
            ->defaultSort('expended_at', 'desc')
            ->deferLoading();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('coreData');
    }

    public static function getNavigationLabel(): string
    {
        return trans_choice('expense', 2);
    }

    public static function getModelLabel(): string
    {
        return trans_choice('expense', 1);
    }

    public static function getPluralModelLabel(): string
    {
        return trans_choice('expense', 2);
    }
}
