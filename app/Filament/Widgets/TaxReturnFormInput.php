<?php

namespace App\Filament\Widgets;

use App\Enums\ExpenseCategory;
use App\Enums\TimeUnit;
use App\Models\Expense;
use App\Models\Invoice;
use Carbon\Carbon;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;

class TaxReturnFormInput extends TableWidget
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
            ->header(view('filament.widgets.table-header', [
                'heading' => __('taxReport'),
                'options' => Invoice::getYearList(),
                'actions' => null,
            ]))
            ->columns([
                TextColumn::make('itr')
                    ->label(__('itr'))
                    ->fontFamily(FontFamily::Mono)
                    ->formatStateUsing(fn(?string $state) => $state ? __('lineN', ['n' => $state]) : ''),
                TextColumn::make('vr')
                    ->label(__('vr'))
                    ->fontFamily(FontFamily::Mono)
                    ->formatStateUsing(fn(?string $state) => $state ? __('lineN', ['n' => $state]) : ''),
                TextColumn::make('rsc')
                    ->label(__('rsc'))
                    ->fontFamily(FontFamily::Mono)
                    ->formatStateUsing(fn(?string $state) => $state ? __('lineN', ['n' => $state]) : ''),
                TextColumn::make('help')
                    ->color('gray')
                    ->label(__('helpText')),
                TextColumn::make('value')
                    ->label(__('value'))
                    ->money('eur')
                    ->fontFamily(FontFamily::Mono)
                    ->alignRight()
                    ->color(fn(array $record) => $record['color'] ?? false)
                    ->copyable()
                    ->copyableState(fn(string $state): string => Number::format(floatval($state))),
            ]);
    }

    public function getTableRecords(): Collection
    {
        $dt = Carbon::create($this->filter, 1, 1);

        [$netEarned, $netUntaxableEarned, $vatEarned] = Invoice::ofTime($dt, TimeUnit::YEAR);
        [$netGoodExpended, $vatGoodExpended] = Expense::ofTime($dt, TimeUnit::YEAR, ExpenseCategory::Good);
        [$netServiceExpended, $vatServiceExpended] = Expense::ofTime($dt, TimeUnit::YEAR, ExpenseCategory::Service);
        [$rentExpended, $vatRentExpended] = Expense::ofTime($dt, TimeUnit::YEAR, ExpenseCategory::Rent);
        [$utilityCostsExpended, $vatUtilityExpended] = Expense::ofTime($dt, TimeUnit::YEAR, ExpenseCategory::Utility);
        [$netMinorAssetsExpended, $vatMinorAssetsExpended] = Expense::ofTime($dt, TimeUnit::YEAR, ExpenseCategory::MinorAssets);
        [$netEdvExpended, $vatEdvExpended] = Expense::ofTime($dt, TimeUnit::YEAR, ExpenseCategory::Edv);
        [$netWorkEquipmentExpended, $vatWorkEquipmentExpended] = Expense::ofTime($dt, TimeUnit::YEAR, ExpenseCategory::WorkEquipment);
        [$netAdvertisingExpended, $vatAdvertisingExpended] = Expense::ofTime($dt, TimeUnit::YEAR, ExpenseCategory::Advertising);

        $netExpended = $netGoodExpended + $netServiceExpended + $rentExpended + $utilityCostsExpended
            + $netMinorAssetsExpended + $netEdvExpended + $netWorkEquipmentExpended + $netAdvertisingExpended;
        $vatExpended = $vatGoodExpended + $vatServiceExpended + $vatMinorAssetsExpended + $vatEdvExpended
            + $vatWorkEquipmentExpended + $vatAdvertisingExpended + $vatRentExpended + $vatUtilityExpended;

        return collect([
            [
                '__key' => 1,
                'itr' => '1 (S)',
                'vr' => null,
                'rsc' => null,
                // Elster requires the income tax return's profit line (Zeile 1 ESt Anlage S) in whole euros
                'value' => round($netEarned + $netUntaxableEarned - $netExpended, 0),
                'help' => __('formLabels')['itr1'],
                'color' => 'primary',
            ],
            [
                '__key' => 2,
                'itr' => null,
                'vr' => '22',
                'rsc' => '15',
                'value' => $netEarned,
                'help' => __('formLabels')['rsc15'],
                'color' => 'primary',
            ],
            [
                '__key' => 3,
                'itr' => null,
                'vr' => '75',
                'rsc' => '16',
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
                'help' => __('formLabels')['rsc17'],
                'color' => 'primary',
            ],
            [
                '__key' => 5,
                'itr' => null,
                'vr' => null,
                'rsc' => '27',
                'value' => $netGoodExpended,
                'help' => __('formLabels')['rsc27'],
                'color' => 'danger',
            ],
            [
                '__key' => 6,
                'itr' => null,
                'vr' => null,
                'rsc' => '29',
                'value' => $netServiceExpended,
                'help' => __('formLabels')['rsc29'],
                'color' => 'danger',
            ],
            [
                '__key' => 7,
                'itr' => null,
                'vr' => null,
                'rsc' => '36',
                'value' => $netMinorAssetsExpended,
                'help' => __('formLabels')['rsc36'],
                'color' => 'danger',
            ],
            [
                '__key' => 8,
                'itr' => null,
                'vr' => null,
                'rsc' => '50',
                'value' => $netEdvExpended,
                'help' => __('formLabels')['rsc50'],
                'color' => 'danger',
            ],
            [
                '__key' => 9,
                'itr' => null,
                'vr' => null,
                'rsc' => '51',
                'value' => $netWorkEquipmentExpended,
                'help' => __('formLabels')['rsc51'],
                'color' => 'danger',
            ],
            [
                '__key' => 10,
                'itr' => null,
                'vr' => null,
                'rsc' => '54',
                'value' => $netAdvertisingExpended,
                'help' => __('formLabels')['rsc54'],
                'color' => 'danger',
            ],
            [
                '__key' => 11,
                'itr' => null,
                'vr' => '79',
                'rsc' => '57',
                'value' => $vatExpended,
                'help' => __('formLabels')['rsc57'],
                'color' => 'danger',
            ],
            [
                '__key' => 12,
                'itr' => null,
                'vr' => null,
                'rsc' => '65',
                'value' => $rentExpended,
                'help' => __('formLabels')['rsc65a'],
                'color' => 'danger',
            ],
            [
                '__key' => 13,
                'itr' => null,
                'vr' => null,
                'rsc' => '65',
                'value' => $utilityCostsExpended,
                'help' => __('formLabels')['rsc65b'],
                'color' => 'danger',
            ],
            [
                '__key' => 14,
                'itr' => null,
                'vr' => '118',
                'rsc' => null,
                'value' => $vatEarned - $vatExpended,
                'help' => __('formLabels')['vr118'],
                'color' => 'danger',
            ],
            [
                '__key' => 15,
                'itr' => null,
                'vr' => null,
                'rsc' => '97',
                'value' => round($netEarned + $vatEarned + $netUntaxableEarned - $netExpended - $vatExpended, 2),
                'help' => __('formLabels')['rsc97'],
                'color' => 'gray',
            ],
        ]);
    }
}
