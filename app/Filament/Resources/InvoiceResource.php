<?php

namespace App\Filament\Resources;

use App\Enums\PricingUnit;
use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Models\Invoice;
use Filament\Forms\Components;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Actions;
use Filament\Tables\Columns;
use Filament\Tables\Columns\Summarizers;
use Filament\Tables\Table;
use Carbon\Carbon;
use NumberFormatter;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;
    protected static ?string $navigationIcon = 'tabler-file-stack';
    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Components\Section::make()
                    ->columns(12)
                    ->schema([
                        Components\Select::make('project_id')
                            ->label(trans_choice('project', 1))
                            ->columnSpan(6)
                            ->relationship('project', 'title')
                            ->searchable()
                            ->preload()
                            ->suffixIcon('tabler-package')
                            ->required(),
                        Components\Toggle::make('transitory')
                            ->label(__('transitory'))
                            ->columnSpan(3)
                            ->inline(false)
                            ->helperText(__('invoice.onlyTransitory')),
                        Components\Toggle::make('undated')
                            ->label(__('undated'))
                            ->columnSpan(3)
                            ->inline(false)
                            ->helperText(__('hidePositionsDate')),
                        Components\TextInput::make('title')
                            ->label(__('title'))
                            ->columnSpan(6)
                            ->required(),
                        Components\Textarea::make('description')
                            ->label(__('description'))
                            ->columnSpan(6)
                            ->autosize()
                            ->maxLength(65535),
                        Components\TextInput::make('price')
                            ->label(__('price'))
                            ->columnSpan(3)
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0.01)
                            ->suffixIcon('tabler-currency-euro')
                            ->required(),
                        Components\Select::make('pricing_unit')
                            ->label(__('pricingUnit'))
                            ->columnSpan(3)
                            ->options(PricingUnit::class)
                            ->suffixIcon('tabler-clock-2')
                            ->required(),
                        Components\TextInput::make('discount')
                            ->label(__('discount'))
                            ->columnSpan(3)
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0.01)
                            ->suffixIcon('tabler-currency-euro')
                            ->helperText(__('priceBeforeTax')),
                        Components\TextInput::make('deduction')
                            ->label(__('deduction'))
                            ->columnSpan(3)
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0.01)
                            ->suffixIcon('tabler-currency-euro')
                            ->helperText(__('priceAfterTax')),
                        Components\Toggle::make('taxable')
                            ->label(__('taxable'))
                            ->columnSpan(3)
                            ->inline(false)
                            ->default(true),
                        Components\TextInput::make('vat_rate')
                            ->label(__('vatRate'))
                            ->columnSpan(3)
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0.01)
                            ->default(0.19)
                            ->suffixIcon('tabler-receipt-tax')
                            ->hidden(fn (Get $get): bool => ! $get('taxable')),
                        Components\DatePicker::make('invoiced_at')
                            ->label(__('invoicedAt'))
                            ->columnSpan(3)
                            ->columnStart(7)
                            ->weekStartsOnMonday()
                            ->suffixIcon('tabler-calendar-up'),
                        Components\DatePicker::make('paid_at')
                            ->label(__('paidAt'))
                            ->columnSpan(3)
                            ->weekStartsOnMonday()
                            ->suffixIcon('tabler-calendar-down'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Columns\ColorColumn::make('project.client.color')
                    ->label(''),
                Columns\TextColumn::make('title')
                    ->label(__('title'))
                    ->searchable()
                    ->sortable()
                    ->description(fn (Invoice $record): string => $record->project?->client?->name)
                    ->tooltip(fn (Invoice $record): string => $record->description),
                Columns\TextColumn::make('price')
                    ->label(__('price'))
                    ->money('eur')
                    ->fontFamily(FontFamily::Mono)
                    ->description(fn (Invoice $record): string => $record->pricing_unit->getLabel())
                    ->summarize(Summarizers\Average::make()->money('eur')),
                Columns\TextColumn::make('net')
                    ->label(__('net'))
                    ->money('eur')
                    ->fontFamily(FontFamily::Mono)
                    ->state(fn (Invoice $record): float => $record->net)
                    ->description(fn (Invoice $record): string => $record->hours . ' ' . trans_choice('hour', $record->hours)),
                Columns\TextColumn::make('total')
                    ->label(__('total'))
                    ->money('eur')
                    ->fontFamily(FontFamily::Mono)
                    ->state(fn (Invoice $record): float => $record->final)
                    ->description(fn (Invoice $record): string => (new NumberFormatter(app()->getLocale(), NumberFormatter::CURRENCY))->formatCurrency($record->vat, 'eur') . ' ' . __('vat')),
                Columns\TextColumn::make('invoiced_at')
                    ->label(__('invoiceDates'))
                    ->date('j. F Y')
                    ->description(fn (Invoice $record): string => $record->paid_at
                        ? Carbon::parse($record->paid_at)->isoFormat('LL')
                        : ''
                    ),
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
                    Actions\ReplicateAction::make()
                        ->icon('tabler-copy')
                        ->excludeAttributes(['invoiced_at', 'paid_at']),
                    Actions\Action::make('download')
                        ->label(__('download'))
                        ->icon('tabler-file-type-pdf')
                        ->url(fn (Invoice $record): string => static::getUrl('download', ['record' => $record]))
                        ->openUrlInNewTab(),
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
            ->defaultSort('created_at', 'desc')
            ->deferLoading();
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PositionsRelationManager::class,
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

}
