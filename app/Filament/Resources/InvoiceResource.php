<?php

namespace App\Filament\Resources;

use App\Enums\InvoiceStatus;
use App\Enums\PricingUnit;
use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Relations;
use App\Models\Invoice;
use App\Models\Project;
use App\Services\InvoiceService;
use Carbon\Carbon;
use Filament\Forms\Components;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions;
use Filament\Tables\Columns;
use Filament\Tables\Filters;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Number;

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
                    ->schema(self::formFields(12, false)),
                Components\Section::make()
                    ->heading(__('currentState'))
                    ->hidden(fn (?Invoice $obj) => !$obj?->project)
                    ->columnSpan(['lg' => 2])
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
                Filters\SelectFilter::make('client')
                    ->label(trans_choice('client', 1))
                    ->relationship('project.client', 'name'),
            ])
            ->actions(
                Actions\ActionGroup::make([
                    Actions\EditAction::make()->icon('tabler-edit')->slideOver()->modalWidth(MaxWidth::Large),
                    Actions\ReplicateAction::make()
                        ->icon('tabler-copy')
                        ->beforeFormFilled(function (Invoice $record) {
                            $record->invoiced_at = null;
                            $record->paid_at = null;
                        })
                        ->form(self::formFields(6, false))
                        ->slideOver()
                        ->modalWidth(MaxWidth::ExtraLarge),
                    Actions\Action::make('pdf')
                        ->label(__('downloadFiletype', ['type' => 'pdf']))
                        ->icon('tabler-file-type-pdf')
                        ->action(function (Invoice $record) {
                            Storage::delete(Storage::allFiles());
                            $file = InvoiceService::generatePdf($record);
                            return response()->download(Storage::path($file));
                        }),
                    Actions\Action::make('xml')
                        ->label(__('downloadFiletype', ['type' => 'xml']))
                        ->icon('tabler-file-type-xml')
                        ->action(function (Invoice $record) {
                            Storage::delete(Storage::allFiles());
                            $file = InvoiceService::generateEn16931Xml($record);
                            return response()->download(Storage::path($file));
                        }),
                    Actions\Action::make('send')
                        ->label(__('send'))
                        ->icon('tabler-mail-forward')
                        ->hidden(fn(Invoice $record) => $record->status != InvoiceStatus::RUNNING)
                        ->url(fn (Invoice $record): string => 'mailto:' . $record->project?->client?->email
                            . '?subject=' . rawurlencode(trans_choice('invoice', 1, [], $record->project?->client?->language)) . ' ' . $record->current_number
                            . '&body=' . rawurlencode(__('email.template.invoicing.body.url', ['title' => $record->project?->title], $record->project?->client?->language))),
                    Actions\Action::make('issue')
                        ->label(__('invoiceIssued'))
                        ->icon('tabler-calendar-up')
                        ->hidden(fn(Invoice $record) => $record->status != InvoiceStatus::RUNNING)
                        ->action(function (Invoice $record) {
                            $record->invoiced_at = now();
                            $record->save();
                            Notification::make()->title(__('invoiceDateSet'))->success()->send();
                            return true;
                        }),
                    Actions\Action::make('paid')
                        ->label(__('invoicePaid'))
                        ->icon('tabler-calendar-down')
                        ->hidden(fn(Invoice $record) => $record->status != InvoiceStatus::SENT)
                        ->form([
                            Components\DatePicker::make('paid_at')
                                ->label(__('paidAt'))
                                ->weekStartsOnMonday()
                                ->suffixIcon('tabler-calendar-down')
                                ->default(now())
                                ->required(),
                        ])
                        ->action(function (array $data, Invoice $record) {
                            $record->paid_at = $data['paid_at'];
                            $record->save();
                            Notification::make()->title(__('PaidDateSet'))->success()->send();
                            return true;
                        }),
                    Actions\Action::make('remind')
                        ->label(__('paymentReminder'))
                        ->icon('tabler-mail-exclamation')
                        ->hidden(fn(Invoice $record) => $record->status != InvoiceStatus::SENT)
                        ->url(fn (Invoice $record): string => 'mailto:' . $record->project?->client?->email
                            . '?subject=' . rawurlencode(__('paymentReminder') . ' ' . trans_choice('invoice', 1, [], $record->project?->client?->language)) . ' ' . $record->final_number
                            . '&body=' . rawurlencode(__('email.template.paymentReminder.body.url', ['number' => $record->final_number], $record->project?->client?->language))),
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
            ->defaultSort('created_at', 'desc')
            ->deferLoading();
    }

    public static function getRelations(): array
    {
        return [
            Relations\PositionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
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

    /**
     * Return a list of components containing form fields
     */
    public static function formFields(int $columns = 12, bool $useSection = true): array
    {
        $half = intval($columns / ($columns > 6 ? 2 : 1));
        $fields = [
            Components\Select::make('project_id')
                ->label(trans_choice('project', 1))
                ->relationship('project', 'title')
                ->getOptionLabelFromRecordUsing(fn (Project $record) => "{$record->title} ({$record->client->name})")
                ->searchable()
                ->preload()
                ->suffixIcon('tabler-package')
                ->required()
                ->columnSpan($half),
            Components\Toggle::make('transitory')
                ->label(__('transitory'))
                ->inline(false)
                ->hintIcon('tabler-info-circle', __('invoice.onlyTransitory'))
                ->columnSpan($half/2),
            Components\Toggle::make('undated')
                ->label(__('undated'))
                ->inline(false)
                ->hintIcon('tabler-info-circle', __('hidePositionsDate'))
                ->columnSpan($half/2),
            Components\TextInput::make('title')
                ->label(__('title'))
                ->required()
                ->columnSpan($half),
            Components\Textarea::make('description')
                ->label(__('description'))
                ->autosize()
                ->maxLength(65535)
                ->columnSpan($half),
            Components\TextInput::make('price')
                ->label(__('price'))
                ->numeric()
                ->step(0.01)
                ->minValue(0.01)
                ->suffixIcon('tabler-currency-euro')
                ->required()
                ->columnSpan($half/2),
            Components\Select::make('pricing_unit')
                ->label(__('pricingUnit'))
                ->options(PricingUnit::class)
                ->suffixIcon('tabler-clock-2')
                ->required()
                ->columnSpan($half/2),
            Components\TextInput::make('discount')
                ->label(__('discount'))
                ->numeric()
                ->step(0.01)
                ->minValue(0.01)
                ->suffixIcon('tabler-currency-euro')
                ->helperText(__('priceBeforeTax'))
                ->columnSpan($half/2),
            Components\TextInput::make('deduction')
                ->label(__('deduction'))
                ->numeric()
                ->step(0.01)
                ->minValue(0.01)
                ->suffixIcon('tabler-currency-euro')
                ->helperText(__('priceAfterTax'))
                ->columnSpan($half/2),
            Components\Toggle::make('taxable')
                ->label(__('taxable'))
                ->inline(false)
                ->default(true)
                ->live()
                ->columnSpan($half/2),
            Components\TextInput::make('vat_rate')
                ->label(__('vatRate'))
                ->numeric()
                ->step(0.01)
                ->minValue(0.01)
                ->default(0.19)
                ->suffixIcon('tabler-receipt-tax')
                ->hidden(fn (Get $get): bool => ! $get('taxable'))
                ->columnSpan($half/2),
            Components\DatePicker::make('invoiced_at')
                ->label(__('invoicedAt'))
                ->weekStartsOnMonday()
                ->suffixIcon('tabler-calendar-up')
                ->columnSpan($half/2),
            Components\DatePicker::make('paid_at')
                ->label(__('paidAt'))
                ->weekStartsOnMonday()
                ->suffixIcon('tabler-calendar-down')
                ->columnSpan($half/2),
        ];

        return $useSection
            ? [Components\Section::make()->columns($columns)->schema($fields)]
            : [Components\Grid::make($columns)->schema($fields)];
    }

}
