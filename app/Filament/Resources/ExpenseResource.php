<?php

namespace App\Filament\Resources;

use App\Enums\ExpenseCategory;
use App\Filament\Resources\ExpenseResource\Pages\ListExpenses;
use App\Models\Expense;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;
    protected static string|\BackedEnum|null $navigationIcon = 'tabler-credit-card';
    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return $schema->components(self::formFields());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('expended_at')
                    ->label(__('expendedAt'))
                    ->date('j. F Y')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('price')
                    ->label(__('gross'))
                    ->money('eur')
                    ->fontFamily(FontFamily::Mono)
                    ->alignment(Alignment::End)
                    ->sortable(),
                IconColumn::make('taxable')
                    ->label(__('taxable'))
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('vat')
                    ->label(__('vat'))
                    ->money('eur')
                    ->fontFamily(FontFamily::Mono)
                    ->state(fn(Expense $record): float => $record->vat)
                    ->color(fn(string $state): string => $state == 0 ? 'gray' : 'normal')
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label(__('quantity'))
                    ->numeric()
                    ->fontFamily(FontFamily::Mono)
                    ->sortable(),
                TextColumn::make('category')
                    ->label(__('category'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('description')
                    ->label(__('description'))
                    ->searchable(),
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
                SelectFilter::make('category')
                    ->label(__('category'))
                    ->options(ExpenseCategory::options()),
            ])
            ->recordActions([
                Action::make('bill')
                    ->label('')
                    ->tooltip(__('bill'))
                    ->icon('tabler-receipt')
                    ->hidden(fn(Expense $record) => !$record->documents()->exists())
                    ->action(function (Expense $record) {
                        $document = $record->documents()->latest()->firstOrFail();
                        return Storage::disk($document->disk)->download($document->path, $document->filename);
                    }),
                ActionGroup::make([
                    EditAction::make()
                        ->icon('tabler-edit')
                        ->schema(self::formFields(6, false))
                        ->slideOver()
                        ->modalWidth(Width::Large)
                        ->using(function (array $data, Expense $record): void {
                            $record->update(Arr::except($data, ['bill', 'bill_original_name']));
                            self::syncBillDocument($record, $data);
                        }),
                    ReplicateAction::make()
                        ->icon('tabler-copy')
                        ->schema(self::formFields(6, false))
                        ->slideOver()
                        ->modalWidth(Width::Large)
                        ->beforeReplicaSaved(function (Expense $replica) {
                            unset($replica->bill, $replica->bill_original_name);
                        })
                        ->after(fn(array $data, Expense $replica) => self::syncBillDocument($replica, $data)),
                    DeleteAction::make()->icon('tabler-trash')->requiresConfirmation(),
                ])
                ->icon('tabler-dots-vertical'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->icon('tabler-trash'),
                ])
                ->icon('tabler-dots-vertical'),
            ])
            ->emptyStateActions([
                CreateAction::make()
                    ->icon('tabler-plus')
                    ->schema(self::formFields(6, false))
                    ->slideOver()
                    ->modalWidth(Width::Large)
                    ->using(fn(array $data): Expense => self::createWithBill($data)),
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
            'index' => ListExpenses::route('/'),
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

    /**
     * Return a list of components containing form fields
     */
    public static function formFields(int $columns = 12, bool $useSection = true): array
    {
        $fields = [
            DatePicker::make('expended_at')
                ->label(__('expendedAt'))
                ->weekStartsOnMonday()
                ->required()
                ->default(now())
                ->suffixIcon('tabler-calendar-dollar')
                ->columnSpanFull(),
            Select::make('category')
                ->label(__('category'))
                ->options(ExpenseCategory::class)
                ->required()
                ->default(ExpenseCategory::Good)
                ->suffixIcon('tabler-tag')
                ->columnSpanFull(),
            TextInput::make('price')
                ->label(__('priceGross'))
                ->numeric()
                ->step(0.01)
                ->suffixIcon('tabler-currency-euro')
                ->columnSpan($columns / 2)
                ->required()
                ->live()
                ->rules([
                    fn(Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                        if ($get('category') !== ExpenseCategory::MinorAssets) {
                            return;
                        }

                        $quantity = (float) ($get('quantity') ?: 0);
                        $rate = $get('taxable') ? (float) ($get('vat_rate') ?: 0) : 0;
                        $net = round(round((float) $value * $quantity, 2) / (1 + $rate), 2);
                        $max = config('business.minor_assets.max_net');

                        if ($net > $max) {
                            $fail(__('minorAssetsNetLimitExceeded', ['max' => $max]));
                        }
                    },
                ]),
            TextInput::make('quantity')
                ->label(__('quantity'))
                ->numeric()
                ->step(1)
                ->minValue(1)
                ->default(1)
                ->suffixIcon('tabler-stack')
                ->columnSpan($columns / 2)
                ->required()
                ->live(),
            TextEntry::make('net_preview')
                ->label(__('priceNet'))
                ->state(function (Get $get): float {
                    $quantity = (float) ($get('quantity') ?: 0);
                    $rate = $get('taxable') ? (float) ($get('vat_rate') ?: 0) : 0;
                    $gross = round((float) ($get('price') ?: 0) * $quantity, 2);

                    return round($gross / (1 + $rate), 2);
                })
                ->money('eur')
                ->columnSpan($columns / 2),
            TextInput::make('taxable_ratio')
                ->label(__('taxableRatio'))
                ->numeric()
                ->step(0.01)
                ->minValue(0)
                ->maxValue(1)
                ->default(1)
                ->suffixIcon('tabler-percentage')
                ->columnSpan($columns / 2)
                ->required(),
            Toggle::make('taxable')
                ->label(__('taxable'))
                ->inline(false)
                ->columnSpan($columns / 2)
                ->default(true)
                ->live(),
            TextInput::make('vat_rate')
                ->label(__('vatRate'))
                ->numeric()
                ->step(0.01)
                ->minValue(0.01)
                ->maxValue(1)
                ->default(0.19)
                ->suffixIcon('tabler-receipt-tax')
                ->columnSpan($columns / 2)
                ->required()
                ->live()
                ->hidden(fn(Get $get): bool => !$get('taxable')),
            Textarea::make('description')
                ->label(__('description'))
                ->maxLength(65535)
                ->columnSpanFull(),
            FileUpload::make('bill')
                ->label(__('bill'))
                ->disk('local')
                ->directory('documents/expenses')
                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                ->storeFileNamesIn('bill_original_name')
                ->openable()
                ->downloadable()
                ->previewable(false)
                ->afterStateHydrated(function (FileUpload $component, Set $set, ?Expense $record) {
                    $document = $record?->documents()->latest()->first();
                    $component->state($document?->path);
                    $set('bill_original_name', $document?->filename);
                })
                ->columnSpanFull(),
            Hidden::make('bill_original_name'),
        ];

        return $useSection
            ? [Section::make()->columnSpan($columns)->schema($fields)->columns($columns)]
            : [Grid::make()->columns($columns)->schema($fields)];
    }

    /**
     * Create an expense from form data and attach its bill, if one was uploaded.
     * Used instead of the default CreateAction process because "bill"/"bill_original_name"
     * are virtual form fields with no matching column on the expenses table.
     */
    public static function createWithBill(array $data): Expense
    {
        $expense = Expense::create(Arr::except($data, ['bill', 'bill_original_name']));
        self::syncBillDocument($expense, $data);

        return $expense;
    }

    /**
     * Keep the expense's bill in sync with the "bill" form field: attach a new
     * document when a file was uploaded or replaced, remove it when cleared, and
     * leave the existing document alone when the field wasn't touched.
     */
    public static function syncBillDocument(Expense $expense, array $data): void
    {
        $path = $data['bill'] ?? null;
        $current = $expense->documents()->latest()->first();

        if ($current?->path === $path) {
            return;
        }

        $current?->delete();

        if ($path === null) {
            return;
        }

        $expense->documents()->create([
            'disk' => config('filesystems.default'),
            'path' => $path,
            'filename' => $data['bill_original_name'] ?? basename($path),
            'mime_type' => Storage::mimeType($path) ?: 'application/octet-stream',
            'size' => Storage::size($path),
        ]);
    }
}
