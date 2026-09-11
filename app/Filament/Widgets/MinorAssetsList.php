<?php

namespace App\Filament\Widgets;

use App\Enums\ExpenseCategory;
use App\Models\Expense;
use App\Models\Invoice;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class MinorAssetsList extends TableWidget
{
    public ?int $filter = null;
    protected int|string|array $columnSpan = 12;

    public function __construct()
    {
        // set default filter to last year
        $this->filter = now()->year - 1;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn() => Expense::query()->where('category', ExpenseCategory::MinorAssets))
            ->header(view('filament.widgets.table-header', [
                'heading' => __('minorAssetsRegister'),
                'description' => __('minorAssetsRegisterDescription', [
                    'min' => config('business.minor_assets.tracking_min_net'),
                    'max' => config('business.minor_assets.max_net'),
                ]),
                'options' => Invoice::getYearList(),
                'actions' => null,
            ]))
            ->columns([
                TextColumn::make('expended_at')
                    ->label(__('expendedAt'))
                    ->date('j. F Y'),
                TextColumn::make('description')
                    ->label(__('description')),
                TextColumn::make('quantity')
                    ->label(__('quantity'))
                    ->numeric()
                    ->fontFamily(FontFamily::Mono)
                    ->sortable(),
                TextColumn::make('price')
                    ->label(__('gross'))
                    ->money('eur')
                    ->fontFamily(FontFamily::Mono)
                    ->alignment(Alignment::End)
                    ->sortable(),
                TextColumn::make('vat')
                    ->label(__('vat'))
                    ->money('eur')
                    ->fontFamily(FontFamily::Mono)
                    ->state(fn(Expense $record): float => $record->vat)
                    ->color(fn(string $state): string => $state == 0 ? 'gray' : 'normal')
                    ->sortable(),
                TextColumn::make('net')
                    ->label(__('net'))
                    ->money('eur')
                    ->fontFamily(FontFamily::Mono)
                    ->alignRight(),
                TextColumn::make('deductible_net')
                    ->label(__('deductibleNet'))
                    ->money('eur')
                    ->fontFamily(FontFamily::Mono)
                    ->state(fn(Expense $record): float => $record->deductibleNet)
                    ->alignRight(),
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
            ]);
    }

    /**
     * List GWG expenses that must be tracked in the asset register (net value between 250 and 800 €, see business config)
     */
    public function getTableRecords(): Collection
    {
        $minNet = config('business.minor_assets.tracking_min_net');
        $maxNet = config('business.minor_assets.max_net');
        $dt = Carbon::create($this->filter, 1, 1);

        return Expense::query()
            ->where('category', ExpenseCategory::MinorAssets)
            ->where('expended_at', '>=', $dt->startOfYear()->toDateString())
            ->where('expended_at', '<=', $dt->endOfYear()->toDateString())
            ->get()
            ->filter(fn(Expense $expense): bool => $expense->net > $minNet && $expense->net <= $maxNet)
            ->sortByDesc('expended_at')
            ->values();
    }
}
