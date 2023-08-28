<?php

namespace App\Filament\Resources;

use App\Enums\PricingUnit;
use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Models\Client;
use App\Models\Project;
use App\Models\Invoice;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ReplicateAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;
    protected static ?string $navigationIcon = 'tabler-file-stack';
    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form
            ->columns(12)
            ->schema([
                Select::make('project_id')
                    ->columnSpan(6)
                    ->translateLabel()
                    ->relationship('project', 'title')
                    ->native(false)
                    ->searchable()
                    ->preload()
                    ->suffixIcon('tabler-package')
                    ->required(),
                Toggle::make('transitory')
                    ->columnSpan(6)
                    ->translateLabel()
                    ->inline(false)
                    ->helperText(__('This invoice only contains transitory items.')),
                TextInput::make('title')
                    ->columnSpan(6)
                    ->translateLabel()
                    ->required(),
                Textarea::make('description')
                    ->columnSpan(6)
                    ->translateLabel()
                    ->autosize()
                    ->maxLength(65535),
                TextInput::make('price')
                    ->columnSpan(3)
                    ->translateLabel()
                    ->numeric()
                    ->step(0.01)
                    ->minValue(0.01)
                    ->suffixIcon('tabler-currency-euro')
                    ->required(),
                Select::make('pricing_unit')
                    ->columnSpan(3)
                    ->translateLabel()
                    ->options(PricingUnit::class)
                    ->native(false)
                    ->suffixIcon('tabler-clock-2')
                    ->required(),
                TextInput::make('discount')
                    ->columnSpan(6)
                    ->translateLabel()
                    ->numeric()
                    ->step(0.01)
                    ->minValue(0.01)
                    ->suffixIcon('tabler-currency-euro')
                    ->helperText(__('Price reduction before taxation.')),
                Toggle::make('taxable')
                    ->columnSpan(1)
                    ->translateLabel()
                    ->inline(false),
                TextInput::make('vat')
                    ->columnSpan(5)
                    ->translateLabel()
                    ->numeric()
                    ->step(0.01)
                    ->minValue(0.01)
                    ->suffixIcon('tabler-receipt-tax')
                    ->required(),
                DatePicker::make('invoiced_at')
                    ->columnSpan(3)
                    ->translateLabel()
                    ->native(false)
                    ->weekStartsOnMonday()
                    ->suffixIcon('tabler-calendar-up'),
                DatePicker::make('paid_at')
                    ->columnSpan(3)
                    ->translateLabel()
                    ->native(false)
                    ->weekStartsOnMonday()
                    ->suffixIcon('tabler-calendar-down'),
                TextInput::make('deduction')
                    ->columnSpan(6)
                    ->translateLabel()
                    ->numeric()
                    ->step(0.01)
                    ->minValue(0.01)
                    ->suffixIcon('tabler-currency-euro')
                    ->helperText(__('Price reduction after taxation.')),
                Toggle::make('undated')
                    ->columnSpan(6)
                    ->translateLabel()
                    ->inline(false)
                    ->helperText(__('Hide date of positions in invoice.')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                CreateAction::make(),
            ])
            ->emptyStateIcon('tabler-ban')
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
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Core data');
    }

    public static function getNavigationLabel(): string
    {
        return __('Invoices');
    }

    public static function getModelLabel(): string
    {
        return __('Invoice');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Invoices');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->orderByDesc('created_at');
    }
}
