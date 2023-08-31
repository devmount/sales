<?php

namespace App\Filament\Resources;

use App\Enums\PricingUnit;
use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Models\Invoice;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ReplicateAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Summarizers\Average;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use NumberFormatter;

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
                    ->label(trans_choice('project', 1))
                    ->columnSpan(6)
                    ->relationship('project', 'title')
                    ->native(false)
                    ->searchable()
                    ->preload()
                    ->suffixIcon('tabler-package')
                    ->required(),
                Toggle::make('transitory')
                    ->label(__('transitory'))
                    ->columnSpan(6)
                    ->inline(false)
                    ->helperText(__('invoice.onlyTransitory')),
                TextInput::make('title')
                    ->label(__('title'))
                    ->columnSpan(6)
                    ->required(),
                Textarea::make('description')
                    ->label(__('description'))
                    ->columnSpan(6)
                    ->autosize()
                    ->maxLength(65535),
                TextInput::make('price')
                    ->label(__('price'))
                    ->columnSpan(3)
                    ->numeric()
                    ->step(0.01)
                    ->minValue(0.01)
                    ->suffixIcon('tabler-currency-euro')
                    ->required(),
                Select::make('pricing_unit')
                    ->label(__('pricingUnit'))
                    ->columnSpan(3)
                    ->options(PricingUnit::class)
                    ->native(false)
                    ->suffixIcon('tabler-clock-2')
                    ->required(),
                TextInput::make('discount')
                    ->label(__('discount'))
                    ->columnSpan(6)
                    ->numeric()
                    ->step(0.01)
                    ->minValue(0.01)
                    ->suffixIcon('tabler-currency-euro')
                    ->helperText(__('priceBeforeTax')),
                Toggle::make('taxable')
                    ->label(__('taxable'))
                    ->columnSpan(1)
                    ->inline(false)
                    ->default(true),
                TextInput::make('vat_rate')
                    ->label(__('vatRate'))
                    ->columnSpan(5)
                    ->numeric()
                    ->step(0.01)
                    ->minValue(0.01)
                    ->suffixIcon('tabler-receipt-tax')
                    ->hidden(fn (Get $get): bool => ! $get('taxable')),
                DatePicker::make('invoiced_at')
                    ->label(__('invoicedAt'))
                    ->columnSpan(3)
                    ->columnStart(7)
                    ->native(false)
                    ->weekStartsOnMonday()
                    ->suffixIcon('tabler-calendar-up'),
                DatePicker::make('paid_at')
                    ->label(__('paidAt'))
                    ->columnSpan(3)
                    ->native(false)
                    ->weekStartsOnMonday()
                    ->suffixIcon('tabler-calendar-down'),
                TextInput::make('deduction')
                    ->label(__('deduction'))
                    ->columnSpan(6)
                    ->numeric()
                    ->step(0.01)
                    ->minValue(0.01)
                    ->suffixIcon('tabler-currency-euro')
                    ->helperText(__('priceAfterTax')),
                Toggle::make('undated')
                    ->label(__('undated'))
                    ->columnSpan(6)
                    ->inline(false)
                    ->helperText(__('hidePositionsDate')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ColorColumn::make('project.client.color')
                    ->label(''),
                TextColumn::make('title')
                    ->label(__('title'))
                    ->searchable()
                    ->sortable()
                    ->description(fn (Invoice $record): string => $record->project?->client?->name)
                    ->tooltip(fn (Invoice $record): string => $record->description),
                TextColumn::make('price')
                    ->label(__('price'))
                    ->money('eur')
                    ->fontFamily(FontFamily::Mono)
                    ->description(fn (Invoice $record): string => $record->pricing_unit->getLabel())
                    ->summarize(Average::make()->money('eur')),
                TextColumn::make('net')
                    ->label(__('net'))
                    ->money('eur')
                    ->fontFamily(FontFamily::Mono)
                    ->state(fn (Invoice $record): float => $record->net)
                    ->description(fn (Invoice $record): string => $record->hours . ' ' . trans_choice('hour', $record->hours)),
                TextColumn::make('total')
                    ->label(__('total'))
                    ->money('eur')
                    ->fontFamily(FontFamily::Mono)
                    ->state(fn (Invoice $record): float => $record->final)
                    ->description(fn (Invoice $record): string => (new NumberFormatter(app()->getLocale(), NumberFormatter::CURRENCY))->formatCurrency($record->vat, 'eur') . ' ' . __('vat')),
                TextColumn::make('created_at')
                    ->label(__('createdAt'))
                    ->datetime('j. F Y, H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('updatedAt'))
                    ->datetime('j. F Y, H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions(
                ActionGroup::make([
                    EditAction::make()->icon('tabler-edit'),
                    ReplicateAction::make()->icon('tabler-copy'),
                    DeleteAction::make()->icon('tabler-trash'),
                    Action::make('download')
                        ->url(fn (Invoice $record): string => static::getUrl('download', ['record' => $record]))
                        ->openUrlInNewTab(),
                ])
                ->icon('tabler-dots-vertical')
            )
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->icon('tabler-trash'),
                ])
                ->icon('tabler-dots-vertical'),
            ])
            ->emptyStateActions([
                CreateAction::make()->icon('tabler-plus'),
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
            'download' => Pages\DownloadInvoice::route('/{record}/download'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('coreData');
    }

    public static function getNavigationLabel(): string
    {
        return trans_choice('invoice', 2);
    }

    public static function getModelLabel(): string
    {
        return trans_choice('invoice', 1);
    }

    public static function getPluralModelLabel(): string
    {
        return trans_choice('invoice', 2);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->orderByDesc('created_at');
    }
}
