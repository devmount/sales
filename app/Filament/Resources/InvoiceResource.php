<?php

namespace App\Filament\Resources;

use App\Enums\PricingUnit;
use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Carbon\Carbon;
use Filament\Forms\Components;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Actions;
use Filament\Tables\Columns;
use Filament\Tables\Table;
use Filament\Tables\Filters;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Number;
use Illuminate\Support\Facades\Storage;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;
    protected static ?string $navigationIcon = 'tabler-file-stack';
    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form
            ->columns(10)
            ->schema([
                Components\Section::make()
                    ->columnSpan(['lg' => fn (?Invoice $obj) => !$obj?->project ? 10 : 8])
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
                            ->hintIcon('tabler-info-circle', __('invoice.onlyTransitory')),
                        Components\Toggle::make('undated')
                            ->label(__('undated'))
                            ->columnSpan(3)
                            ->inline(false)
                            ->hintIcon('tabler-info-circle', __('hidePositionsDate')),
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
                            ->default(true)
                            ->live(),
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
                    ]),
                Components\Section::make()
                    ->heading(__('currentState'))
                    ->hidden(fn (?Invoice $obj) => !$obj?->project)
                    ->columnSpan(['lg' => 2])
                    ->columns(2)
                    ->schema([
                        Components\Placeholder::make('project')
                            ->label(trans_choice('project', 1))
                            ->content(fn (Invoice $obj) => new HtmlString(
                                $obj->project?->hours
                                . ' / ' . $obj->project?->scope_range
                                . ($obj->project?->scope
                                    ? '<br />' . __('numExhausted', ['n' => $obj->project?->progress_percent])
                                    : ''
                                )
                            ))
                            ->columnSpanFull(),
                        Components\Placeholder::make('invoice')
                            ->label(trans_choice('invoice', 1))
                            ->content(fn (Invoice $obj) => new HtmlString(
                                count($obj->positions) . ' ' . trans_choice('position', count($obj->positions))
                                . '<br />' . $obj->hours_formatted
                                . '<br />' . $obj->net_formatted . ' ' . __('net')
                            ))
                            ->columnSpanFull(),
                    ]),
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
                    ->tooltip(fn (Invoice $record): ?string => $record->description),
                Columns\TextColumn::make('price')
                    ->label(__('price'))
                    ->money('eur')
                    ->fontFamily(FontFamily::Mono)
                    ->description(fn (Invoice $record): string => $record->pricing_unit->getLabel()),
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
                    ->description(fn (Invoice $record): string => Number::currency($record->vat, 'eur') . ' ' . __('vat')),
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
                Filters\SelectFilter::make('project')
                    ->label(trans_choice('project', 1))
                    ->relationship('project', 'title'),
            ])
            ->actions(
                Actions\ActionGroup::make([
                    Actions\EditAction::make()->icon('tabler-edit'),
                    Actions\ReplicateAction::make()
                        ->icon('tabler-copy')
                        ->excludeAttributes(['invoiced_at', 'paid_at']),
                    Actions\Action::make('pdf')
                        ->label(__('downloadFiletype', ['type' => 'pdf']))
                        ->icon('tabler-file-type-pdf')
                        ->action(function (Invoice $record) {
                            $file = InvoiceService::generatePdf($record);
                            return response()->download(Storage::path($file));
                        }),
                    Actions\Action::make('xml')
                        ->label(__('downloadFiletype', ['type' => 'xml']))
                        ->icon('tabler-file-type-xml')
                        ->action(function (Invoice $record) {
                            $file = InvoiceService::generateEn16931Xml($record);
                            return response()->download(Storage::path($file));
                        }),
                    Actions\Action::make('send')
                        ->label(__('send'))
                        ->icon('tabler-mail-forward')
                        ->url(fn (Invoice $record): string => 'mailto:' . $record->project?->client?->email
                            . '?subject=' . rawurlencode(trans_choice('invoice', 1, [], $record->project?->client?->language)) . ' ' . $record->current_number
                            . '&body=' . rawurlencode(__('email.template.invoicing.body.url', ['title' => $record->project?->title], $record->project?->client?->language))),
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
