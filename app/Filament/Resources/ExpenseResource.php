<?php

namespace App\Filament\Resources;

use App\Enums\ExpenseCategory;
use App\Filament\Resources\ExpenseResource\Pages;
use App\Filament\Resources\ExpenseResource\RelationManagers;
use App\Models\Expense;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'tabler-credit-card';

    public static function form(Form $form): Form
    {
        return $form
            ->columns(6)
            ->schema([
                DatePicker::make('expended_at')
                    ->translateLabel()
                    ->native(false)
                    ->weekStartsOnMonday()
                    ->required()
                    ->columnSpan(3),
                Select::make('category')
                    ->translateLabel()
                    ->options(ExpenseCategory::class)
                    ->native(false)
                    ->required()
                    ->columnSpan(3),
                TextInput::make('price')
                    ->translateLabel()
                    ->numeric()
                    ->step(0.01)
                    ->minValue(0.01)
                    ->suffix('EUR')
                    ->required()
                    ->columnSpan(3),
                Toggle::make('taxable')
                    ->translateLabel()
                    ->inline(false)
                    ->required()
                    ->columnSpan(1),
                TextInput::make('vat')
                    ->translateLabel()
                    ->numeric()
                    ->step(0.01)
                    ->minValue(0.01)
                    ->maxValue(1)
                    ->required()
                    ->columnSpan(2)
                    ->hidden(fn (Get $get): bool => ! $get('taxable')),
                TextInput::make('quantity')
                    ->translateLabel()
                    ->numeric()
                    ->step(1)
                    ->minValue(1)
                    ->required()
                    ->columnSpan(3),
                Textarea::make('description')
                    ->translateLabel()
                    ->maxLength(65535)
                    ->columnSpan(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('expended_at')
                    ->translateLabel()
                    ->date('j. F Y')
                    ->sortable(),
                TextColumn::make('price')
                    ->translateLabel()
                    ->money('eur')
                    ->fontFamily(FontFamily::Mono)
                    ->alignment(Alignment::End)
                    ->sortable()
                    ->summarize(Sum::make()->money('eur')),
                IconColumn::make('taxable')
                    ->translateLabel()
                    ->boolean()
                    ->sortable(),
                TextColumn::make('vat')
                    ->translateLabel()
                    ->numeric()
                    ->sortable(),
                TextColumn::make('quantity')
                    ->translateLabel()
                    ->numeric()
                    ->sortable()
                    ->summarize(Sum::make()),
                TextColumn::make('category')
                    ->translateLabel()
                    ->badge()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->translateLabel()
                    ->datetime('j. F Y, H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->translateLabel()
                    ->datetime('j. F Y, H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions(ActionGroup::make([
                EditAction::make(),
            ]))
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                CreateAction::make(),
            ]);
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

    public static function getNavigationLabel(): string
    {
        return __('Expenses');
    }

    public static function getModelLabel(): string
    {
        return __('Expense');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Expenses');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->orderByDesc('expended_at');
    }
}
