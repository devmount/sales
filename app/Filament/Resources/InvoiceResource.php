<?php

namespace App\Filament\Resources;

use App\Enums\InvoiceStatus;
use App\Enums\PricingUnit;
use App\Filament\Relations\PositionsRelationManager;
use App\Filament\Resources\InvoiceResource\Pages\EditInvoice;
use App\Filament\Resources\InvoiceResource\Pages\ListInvoices;
use App\Models\Invoice;
use App\Models\Project;
use App\Services\InvoiceService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Number;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;
    protected static string | \BackedEnum | null $navigationIcon = 'tabler-file-stack';
    protected static ?int $navigationSort = 30;

    public static function getNavigationBadge(): ?string
    {
        return Invoice::waiting()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return Invoice::waiting()->count() > 0 ? 'warning' : 'gray';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        $waitingNet = Invoice::waiting()->get()->reduce(fn($p, $c) => $p + $c->net, 0);
        return __('waitingForPayment', ['net' => Number::currency($waitingNet, 'eur') ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(10)
            ->components([
                Section::make()
                    ->columnSpan(['lg' => fn (?Invoice $obj) => !$obj?->project ? 10 : 8])
                    ->schema(self::formFields(12, false)),
                Section::make()
                    ->heading(__('currentState'))
                    ->hidden(fn (?Invoice $obj) => !$obj?->project)
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        TextEntry::make('project')
                            ->label(trans_choice('project', 1))
                            ->state(fn (Invoice $obj) => new HtmlString(
                                $obj->project?->hours
                                . ' / ' . $obj->project?->scope_range
                                . ($obj->project?->scope
                                    ? '<br />' . __('numExhausted', ['n' => $obj->project?->progress_percent])
                                    : ''
                                )
                            ))
                            ->columnSpanFull(),
                        TextEntry::make('invoice')
                            ->label(trans_choice('invoice', 1))
                            ->state(fn (Invoice $obj) => new HtmlString(
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
                ColorColumn::make('project.client.color')
                    ->label(''),
                TextColumn::make('title')
                    ->label(__('title'))
                    ->searchable()
                    ->sortable()
                    ->description(fn (Invoice $record): string => $record->project?->client?->name)
                    ->tooltip(fn (Invoice $record): ?string => $record->description),
                TextColumn::make('price')
                    ->label(__('price'))
                    ->money('eur')
                    ->fontFamily(FontFamily::Mono)
                    ->description(fn (Invoice $record): string => $record->pricing_unit->getLabel()),
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
                    ->description(fn (Invoice $record): string => Number::currency($record->vat, 'eur') . ' ' . __('vat')),
                TextColumn::make('invoiced_at')
                    ->label(__('invoiceDates'))
                    ->date('j. F Y')
                    ->description(fn (Invoice $record): string => $record->paid_at
                        ? Carbon::parse($record->paid_at)->isoFormat('LL')
                        : ''
                    ),
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
                SelectFilter::make('project')
                    ->label(trans_choice('project', 1))
                    ->relationship('project', 'title'),
                SelectFilter::make('client')
                    ->label(trans_choice('client', 1))
                    ->relationship('project.client', 'name'),
            ])
            ->recordActions(
                ActionGroup::make([
                    EditAction::make()->icon('tabler-edit')->slideOver()->modalWidth(Width::Large),
                    ReplicateAction::make()
                        ->icon('tabler-copy')
                        ->beforeFormFilled(function (Invoice $record) {
                            $record->invoiced_at = null;
                            $record->paid_at = null;
                        })
                        ->schema(self::formFields(6, false))
                        ->slideOver()
                        ->modalWidth(Width::ExtraLarge),
                    Action::make('pdf')
                        ->label(__('downloadFiletype', ['type' => 'pdf']))
                        ->icon('tabler-file-type-pdf')
                        ->action(function (Invoice $record) {
                            Storage::delete(Storage::allFiles());
                            $file = InvoiceService::generatePdf($record);
                            return response()->download(Storage::path($file));
                        }),
                    Action::make('xml')
                        ->label(__('downloadFiletype', ['type' => 'xml']))
                        ->icon('tabler-file-type-xml')
                        ->action(function (Invoice $record) {
                            Storage::delete(Storage::allFiles());
                            $file = InvoiceService::generateEn16931Xml($record);
                            return response()->download(Storage::path($file));
                        }),
                    Action::make('send')
                        ->label(__('send'))
                        ->icon('tabler-mail-forward')
                        ->hidden(fn(Invoice $record) => $record->status != InvoiceStatus::RUNNING)
                        ->url(fn (Invoice $record): string => 'mailto:' . $record->project?->client?->email
                            . '?subject=' . rawurlencode(trans_choice('invoice', 1, [], $record->project?->client?->language)) . ' ' . $record->current_number
                            . '&body=' . rawurlencode(__('email.template.invoicing.body.url', ['title' => $record->project?->title], $record->project?->client?->language))),
                    Action::make('issue')
                        ->label(__('invoiceIssued'))
                        ->icon('tabler-calendar-up')
                        ->hidden(fn(Invoice $record) => $record->status != InvoiceStatus::RUNNING)
                        ->action(function (Invoice $record) {
                            $record->invoiced_at = now();
                            $record->save();
                            Notification::make()->title(__('invoiceDateSet'))->success()->send();
                            return true;
                        }),
                    Action::make('paid')
                        ->label(__('invoicePaid'))
                        ->icon('tabler-calendar-down')
                        ->hidden(fn(Invoice $record) => $record->status != InvoiceStatus::SENT)
                        ->schema([
                            DatePicker::make('paid_at')
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
                    Action::make('remind')
                        ->label(__('paymentReminder'))
                        ->icon('tabler-mail-exclamation')
                        ->hidden(fn(Invoice $record) => $record->status != InvoiceStatus::SENT)
                        ->url(fn (Invoice $record): string => 'mailto:' . $record->project?->client?->email
                            . '?subject=' . rawurlencode(__('paymentReminder') . ' ' . trans_choice('invoice', 1, [], $record->project?->client?->language)) . ' ' . $record->final_number
                            . '&body=' . rawurlencode(__('email.template.paymentReminder.body.url', ['number' => $record->final_number], $record->project?->client?->language))),
                    DeleteAction::make()->icon('tabler-trash')->requiresConfirmation(),
                ])
                ->icon('tabler-dots-vertical')
            )
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->icon('tabler-trash'),
                ])
                ->icon('tabler-dots-vertical'),
            ])
            ->emptyStateActions([
                CreateAction::make()->icon('tabler-plus')->slideOver()->modalWidth(Width::Large),
            ])
            ->emptyStateIcon('tabler-ban')
            ->defaultSort('created_at', 'desc')
            ->deferLoading();
    }

    public static function getRelations(): array
    {
        return [
            PositionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoices::route('/'),
            'edit' => EditInvoice::route('/{record}/edit'),
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
            Select::make('project_id')
                ->label(trans_choice('project', 1))
                ->relationship('project', 'title')
                ->getOptionLabelFromRecordUsing(fn (Project $record) => "{$record->title} ({$record->client->name})")
                ->searchable()
                ->preload()
                ->suffixIcon('tabler-package')
                ->required()
                ->columnSpan($half),
            Toggle::make('transitory')
                ->label(__('transitory'))
                ->inline(false)
                ->hintIcon('tabler-info-circle', __('invoice.onlyTransitory'))
                ->columnSpan($half/2),
            Toggle::make('undated')
                ->label(__('undated'))
                ->inline(false)
                ->hintIcon('tabler-info-circle', __('hidePositionsDate'))
                ->columnSpan($half/2),
            TextInput::make('title')
                ->label(__('title'))
                ->required()
                ->columnSpan($half),
            Textarea::make('description')
                ->label(__('description'))
                ->autosize()
                ->maxLength(65535)
                ->columnSpan($half),
            TextInput::make('price')
                ->label(__('price'))
                ->numeric()
                ->step(0.01)
                ->minValue(0.01)
                ->suffixIcon('tabler-currency-euro')
                ->required()
                ->columnSpan($half/2),
            Select::make('pricing_unit')
                ->label(__('pricingUnit'))
                ->options(PricingUnit::class)
                ->suffixIcon('tabler-clock-2')
                ->required()
                ->columnSpan($half/2),
            TextInput::make('discount')
                ->label(__('discount'))
                ->numeric()
                ->step(0.01)
                ->minValue(0.01)
                ->suffixIcon('tabler-currency-euro')
                ->helperText(__('priceBeforeTax'))
                ->columnSpan($half/2),
            TextInput::make('deduction')
                ->label(__('deduction'))
                ->numeric()
                ->step(0.01)
                ->minValue(0.01)
                ->suffixIcon('tabler-currency-euro')
                ->helperText(__('priceAfterTax'))
                ->columnSpan($half/2),
            Toggle::make('taxable')
                ->label(__('taxable'))
                ->inline(false)
                ->default(true)
                ->live()
                ->columnSpan($half/2),
            TextInput::make('vat_rate')
                ->label(__('vatRate'))
                ->numeric()
                ->step(0.01)
                ->minValue(0.01)
                ->default(0.19)
                ->suffixIcon('tabler-receipt-tax')
                ->hidden(fn (Get $get): bool => ! $get('taxable'))
                ->columnSpan($half/2),
            DatePicker::make('invoiced_at')
                ->label(__('invoicedAt'))
                ->weekStartsOnMonday()
                ->suffixIcon('tabler-calendar-up')
                ->columnSpan($half/2),
            DatePicker::make('paid_at')
                ->label(__('paidAt'))
                ->weekStartsOnMonday()
                ->suffixIcon('tabler-calendar-down')
                ->columnSpan($half/2),
        ];

        return $useSection
            ? [Section::make()->columnSpan($columns)->schema($fields)->columns($columns)]
            : [Grid::make($columns)->schema($fields)];
    }

}
