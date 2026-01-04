<?php

namespace App\Filament\Widgets;

use App\Enums\ExpenseCategory;
use App\Enums\TimeUnit;
use App\Models\Expense;
use App\Models\Invoice;
use Carbon\Carbon;
use Filament\Support\Enums\FontFamily as EnumsFontFamily;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;

class TaxReturnFormInput extends TableWidget
{
    protected int | string | array $columnSpan = 12;
    public ?int $filter = null;

    public function __construct()
    {
        // set default filter to last year
        $this->filter = now()->year - 1;
    }

    public function table(Table $table): Table
    {
        return $table
            ->header(view('filament.widgets.table-header', [
                'heading' => __('taxReport'),
                'options' => Invoice::getYearList(),
            ]))
            ->columns([
                TextColumn::make('itr')
                    ->label(__('itr'))
                    ->fontFamily(EnumsFontFamily::Mono)
                    ->formatStateUsing(fn (?string $state) => $state ? __('lineN', ['n' => $state]) : ''),
                TextColumn::make('vr')
                    ->label(__('vr'))
                    ->fontFamily(EnumsFontFamily::Mono)
                    ->formatStateUsing(fn (?string $state) => $state ? __('lineN', ['n' => $state]) : ''),
                TextColumn::make('rsc')
                    ->label(__('rsc'))
                    ->fontFamily(EnumsFontFamily::Mono)
                    ->formatStateUsing(fn (?string $state) => $state ? __('lineN', ['n' => $state]) : ''),
                TextColumn::make('value')
                    ->label(__('value'))
                    ->money('eur')
                    ->fontFamily(EnumsFontFamily::Mono)
                    ->alignRight()
                    ->color(fn(array $record) => $record['color'] ?? false)
                    ->copyable()
                    ->copyableState(fn (string $state): string => Number::format(floatval($state))),
                TextColumn::make('help')
                    ->color('gray')
                    ->label(__('helpText')),
            ]);
    }

    public function getTableRecords(): Collection
    {
        $dt = Carbon::create($this->filter, 1, 1);
        [$netEarned, $netUntaxableEarned, $vatEarned] = Invoice::ofTime($dt, TimeUnit::YEAR); // TODO: Untaxable income
        [$netGoodExpended, $vatGoodExpended] = Expense::ofTime($dt, TimeUnit::YEAR, ExpenseCategory::Good);
        [$netServiceExpended, $vatServiceExpended] = Expense::ofTime($dt, TimeUnit::YEAR, ExpenseCategory::Service);
        $netExpended = $netGoodExpended + $netServiceExpended;
        $vatExpended = $vatGoodExpended + $vatServiceExpended;

        return collect([
            [
                '__key' => 1,
                'itr' => '1 (S)',
                'vr' => null,
                'rsc' => null,
                'value' => round($netEarned - $netExpended),
                'help' => __('formLabels')['itr1'],
                'color' => 'primary',
            ],
            [
                '__key' => 2,
                'itr' => null,
                'vr' => '22',
                'rsc' => '15',
                'value' => $netEarned,
                'help' => __('formLabels')['rsc14'],
                'color' => 'primary',
            ],
            [
                '__key' => 3,
                'itr' => null,
                'vr' => '75',
                'rsc' => null,
                'value' => $netUntaxableEarned,
                'help' => __('formLabels')['vr75'],
                'color' => 'primary',
            ],
            [
                '__key' => 4,
                'itr' => null,
                'vr' => null,
                'rsc' => '17',
                'value' => $vatEarned,
                'help' => __('formLabels')['rsc16'],
                'color' => 'primary',
            ],
            [
                '__key' => 5,
                'itr' => null,
                'vr' => null,
                'rsc' => '27',
                'value' => $netGoodExpended,
                'help' => __('formLabels')['rsc26'],
                'color' => 'danger',
            ],
            [
                '__key' => 6,
                'itr' => null,
                'vr' => null,
                'rsc' => '29',
                'value' => $netServiceExpended,
                'help' => __('formLabels')['rsc27'],
                'color' => 'danger',
            ],
            [
                '__key' => 7,
                'itr' => null,
                'vr' => '79',
                'rsc' => '57',
                'value' => $vatExpended,
                'help' => __('formLabels')['rsc55'],
                'color' => 'danger',
            ],
            [
                '__key' => 8,
                'itr' => null,
                'vr' => '118',
                'rsc' => null,
                'value' => $vatEarned - $vatExpended,
                'help' => __('formLabels')['vr118'],
                'color' => 'danger',
            ],
            [
                '__key' => 9,
                'itr' => null,
                'vr' => null,
                'rsc' => '97',
                'value' => $netEarned + $vatEarned - $netExpended - $vatExpended,
                'help' => __('formLabels')['rsc97'],
                'color' => 'gray',
            ],
        ]);
    }
}
