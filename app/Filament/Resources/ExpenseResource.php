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
use Filament\Resources\Resource;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontFamily;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('expended_at')
                    ->translateLabel()
                    ->native(false)
                    ->weekStartsOnMonday()
                    ->required(),
                TextInput::make('price')
                    ->translateLabel()
                    ->numeric()
                    ->step(0.01)
                    ->minValue(0.01)
                    ->suffix('EUR')
                    ->required(),
                Toggle::make('taxable')
                    ->translateLabel()
                    ->required(),
                TextInput::make('vat')
                    ->translateLabel()
                    ->numeric()
                    ->step(0.01)
                    ->minValue(0.01)
                    ->maxValue(1)
                    ->suffix('%')
                    ->required(),
                TextInput::make('quantity')
                    ->translateLabel()
                    ->numeric()
                    ->step(1)
                    ->minValue(1)
                    ->required(),
                Select::make('category')
                    ->options(ExpenseCategory::class)
                    ->required(),
                Textarea::make('description')
                    ->maxLength(65535),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('expended_at')
                    ->translateLabel()
                    ->date('j. F Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->translateLabel()
                    ->money('eur')
                    ->fontFamily(FontFamily::Mono)
                    ->alignment(Alignment::End)
                    ->sortable()
                    ->summarize(Sum::make()->money('eur')),
                Tables\Columns\IconColumn::make('taxable')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vat')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->numeric()
                    ->sortable()
                    ->summarize(Sum::make()),
                Tables\Columns\SelectColumn::make('category')
                    ->options(Status::class),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
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
