<?php

namespace App\Filament\Widgets;

use App\Enums\TimeUnit;
use App\Models\Expense;
use App\Models\Invoice;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;

class TaxOverview extends TableWidget implements HasActions
{
    use InteractsWithActions;

    protected int | string | array $columnSpan = 12;
    protected static int $entryCount = 14;
    public ?string $filter = 'm';

    public function lastAdvanceVatAction(): Action
    {
        return Action::make('lastAdvanceVat')
            ->label(__('createLatestVatExpense'))
            ->icon('tabler-credit-card')
            ->outlined()
            ->disabled(Expense::lastAdvanceVatExists())
            ->action(function () {
                if (Expense::saveLastAdvanceVat()) {
                    Notification::make()->title(__('vatExpenseCreated'))->success()->send();
                    redirect('/expenses?activeTab=tax');
                }
            });
    }

    public function table(Table $table): Table
    {
        return $table
            ->header(view('filament.widgets.table-header', [
                'heading' => __('advanceVat'),
                'options' => [
                    'm' => __('perMonth'),
                    'q' => __('perQuarter'),
                    'y' => __('perYear'),
                ],
                'actions' => [
                    $this->lastAdvanceVatAction(),
                ],
            ]))
            ->columns([
                TextColumn::make('unit')
                    ->label(''),
                TextColumn::make('netTaxable')
                    ->label(__('netTaxable'))
                    ->money('eur')
                    ->fontFamily(FontFamily::Mono)
                    ->color(fn (string $state): string => !$state ? 'gray' : 'primary')
                    ->alignRight()
                    ->copyable()
                    ->copyableState(fn (string $state): string => Number::format(floatval($state))),
                TextColumn::make('netUntaxable')
                    ->label(__('netUntaxable'))
                    ->money('eur')
                    ->fontFamily(FontFamily::Mono)
                    ->color(fn (string $state): string => !$state ? 'gray' : 'primary')
                    ->alignRight()
                    ->copyable()
                    ->copyableState(fn (string $state): string => Number::format(floatval($state))),
                TextColumn::make('totalNet')
                    ->label(__('netTotal'))
                    ->money('eur')
                    ->fontFamily(FontFamily::Mono)
                    ->color('gray')
                    ->alignRight()
                    ->copyable()
                    ->copyableState(fn (string $state): string => Number::format(floatval($state))),
                TextColumn::make('vatExpenses')
                    ->label(__('vatExpenses'))
                    ->money('eur')
                    ->fontFamily(FontFamily::Mono)
                    ->color(fn (string $state): string => !$state ? 'gray' : 'danger')
                    ->alignRight()
                    ->copyable()
                    ->copyableState(fn (string $state): string => Number::format(floatval($state))),
                TextColumn::make('totalVat')
                    ->label(__('totalVat'))
                    ->money('eur')
                    ->fontFamily(FontFamily::Mono)
                    ->color(fn (string $state): string => !$state ? 'gray' : 'normal')
                    ->alignRight()
                    ->copyable()
                    ->copyableState(fn (string $state): string => Number::format(floatval($state))),
            ]);
    }

    public function getTableRecords(): Collection
    {
        return match($this->filter) {
            'm' => $this->getMonthData(),
            'q' => $this->getQuarterData(),
            'y' => $this->getYearData(),
        };
    }

    private function getMonthData(): Collection
    {
        $records = [];
        $dt = Carbon::today();

        for ($i=0; $i < static::$entryCount; $i++) {
            [$netTaxable, $netUntaxable, $vatEarned] = Invoice::ofTime($dt, TimeUnit::MONTH);
            [, $vatExpended] = Expense::ofTime($dt, TimeUnit::MONTH);

            $records[] = [
                '__key' => $i,
                'unit' => $dt->year . ' ' . $dt->locale(app()->getLocale())->monthName,
                'netTaxable' => $netTaxable,
                'netUntaxable' => $netUntaxable,
                'totalNet' => $netTaxable + $netUntaxable,
                'vatExpenses' => $vatExpended,
                'totalVat' => $vatEarned - $vatExpended,
            ];

            $dt->subMonthsNoOverflow();
        }

        return collect($records);
    }

    private function getQuarterData(): Collection
    {
        $records = [];
        $dt = Carbon::today();

        for ($i=0; $i < static::$entryCount; $i++) {
            [$netTaxable, $netUntaxable, $vatEarned] = Invoice::ofTime($dt, TimeUnit::QUARTER);
            [, $vatExpended] = Expense::ofTime($dt, TimeUnit::QUARTER);

            $records[] = [
                '__key' => $i,
                'unit' => "$dt->year Q$dt->quarter",
                'netTaxable' => $netTaxable,
                'netUntaxable' => $netUntaxable,
                'totalNet' => $netTaxable + $netUntaxable,
                'vatExpenses' => $vatExpended,
                'totalVat' => $vatEarned - $vatExpended,
            ];

            $dt->subQuarterNoOverflow();
        }

        return collect($records);
    }

    private function getYearData(): Collection
    {
        $records = [];
        $dt = Carbon::today();

        for ($i=0; $i < static::$entryCount; $i++) {
            [$netTaxable, $netUntaxable, $vatEarned] = Invoice::ofTime($dt, TimeUnit::YEAR);
            [, $vatExpended] = Expense::ofTime($dt, TimeUnit::YEAR);

            $records[] = [
                '__key' => $i,
                'unit' => $dt->year,
                'netTaxable' => $netTaxable,
                'netUntaxable' => $netUntaxable,
                'totalNet' => $netTaxable + $netUntaxable,
                'vatExpenses' => $vatExpended,
                'totalVat' => $vatEarned - $vatExpended,
            ];

            $dt->subYearNoOverflow();
        }

        return collect($records);
    }
}
