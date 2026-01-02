<?php

namespace App\Filament\Widgets;

use App\Enums\TimeUnit;
use App\Models\Expense;
use App\Models\Invoice;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Widgets\Widget;
use Illuminate\Support\Number;

class TaxOverview extends Widget implements HasForms, HasInfolists, HasActions
{
    use InteractsWithActions;
    use InteractsWithInfolists;
    use InteractsWithForms;

    protected int | string | array $columnSpan = 8;
    protected string $view = 'filament.widgets.tax-overview';
    protected static int $entryCount = 14;
    public ?string $filter = 'm';

    public function getHeading(): string
    {
        return __('advanceVat');
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->key('taxOverview')
            ->components([
                match($this->filter) {
                    'm' => Grid::make(12)->schema($this->getMonthData()),
                    'q' => Grid::make(12)->schema($this->getQuarterData()),
                    'y' => Grid::make(12)->schema($this->getYearData()),
                },
                Actions::make([
                    Action::make('lastAdvanceVat')
                        ->label(__('createLatestVatExpense'))
                        ->icon('tabler-credit-card')
                        ->outlined()
                        ->disabled(Expense::lastAdvanceVatExists())
                        ->action(function (): void {
                            if (Expense::saveLastAdvanceVat()) {
                                Notification::make()->title(__('vatExpenseCreated'))->success()->send();
                                redirect('/expenses?activeTab=tax');
                            }
                        })
                ])->alignRight()
            ]);
    }

    protected function getFilters(): ?array
    {
        return [
            'm' => __('perMonth'),
            'q' => __('perQuarter'),
            'y' => __('perYear'),
        ];
    }

    private function generateEntries($heading, $labels, $netTaxable, $netUntaxable, $vatExpenses, $totalVat): array
    {
        return [
            TextEntry::make('timeUnit')
                ->label($heading)
                ->columnSpan(2)
                ->fontFamily(FontFamily::Mono)
                ->state($labels)
                ->listWithLineBreaks(),
            TextEntry::make('netTaxable')
                ->label(__('netTaxable'))
                ->columnSpan(3)
                ->money('eur')
                ->fontFamily(FontFamily::Mono)
                ->state($netTaxable)
                ->color(fn (string $state): string => !$state ? 'gray' : 'normal')
                ->listWithLineBreaks()
                ->alignRight()
                ->copyable(fn (string $state): string => floatval($state) > 0)
                ->copyableState(fn (string $state): string => Number::format(floatval($state))),
            TextEntry::make('netUntaxable')
                ->label(__('netUntaxable'))
                ->columnSpan(3)
                ->money('eur')
                ->fontFamily(FontFamily::Mono)
                ->state($netUntaxable)
                ->color(fn (string $state): string => !$state ? 'gray' : 'normal')
                ->listWithLineBreaks()
                ->alignRight()
                ->copyable(fn (string $state): string => floatval($state) > 0)
                ->copyableState(fn (string $state): string => Number::format(floatval($state))),
            TextEntry::make('vatExpenses')
                ->label(__('vatExpenses'))
                ->columnSpan(2)
                ->money('eur')
                ->fontFamily(FontFamily::Mono)
                ->state($vatExpenses)
                ->color(fn (string $state): string => !$state ? 'gray' : 'normal')
                ->listWithLineBreaks()
                ->alignRight()
                ->copyable(fn (string $state): string => floatval($state) > 0)
                ->copyableState(fn (string $state): string => Number::format(floatval($state))),
            TextEntry::make('totalVat')
                ->label(__('totalVat'))
                ->columnSpan(2)
                ->money('eur')
                ->fontFamily(FontFamily::Mono)
                ->state($totalVat)
                ->color(fn (string $state): string => !$state ? 'gray' : 'normal')
                ->listWithLineBreaks()
                ->alignRight()
                ->copyable(fn (string $state): string => floatval($state) > 0)
                ->copyableState(fn (string $state): string => Number::format(floatval($state))),
            ];
    }

    private function getMonthData(): array
    {
        $labels = [];
        $netIncomeTaxable = [];
        $netIncomeUntaxable = [];
        $vatExpenses = [];
        $totalVat = [];

        $dt = Carbon::today();
        for ($i=0; $i < static::$entryCount; $i++) {
            $labels[] = $dt->locale(app()->getLocale())->monthName;
            [$netTaxable, $netUntaxable, $vatEarned] = Invoice::ofTime($dt, TimeUnit::MONTH);
            [, $vatExpended] = Expense::ofTime($dt, TimeUnit::MONTH);
            $netIncomeTaxable[] = $netTaxable;
            $netIncomeUntaxable[] = $netUntaxable;
            $vatExpenses[] = $vatExpended;
            $totalVat[] = $vatEarned - $vatExpended;
            $dt->subMonthsNoOverflow();
        }
        return $this->generateEntries(
            __('month'), $labels, $netIncomeTaxable, $netIncomeUntaxable, $vatExpenses, $totalVat
        );
    }

    private function getQuarterData(): array
    {
        $labels = [];
        $netIncomeTaxable = [];
        $netIncomeUntaxable = [];
        $vatExpenses = [];
        $totalVat = [];

        $dt = Carbon::today();
        for ($i=0; $i < static::$entryCount; $i++) {
            $labels[] = "$dt->year Q$dt->quarter";
            [$netTaxable, $netUntaxable, $vatEarned] = Invoice::ofTime($dt, TimeUnit::QUARTER);
            [, $vatExpended] = Expense::ofTime($dt, TimeUnit::QUARTER);
            $netIncomeTaxable[] = $netTaxable;
            $netIncomeUntaxable[] = $netUntaxable;
            $vatExpenses[] = $vatExpended;
            $totalVat[] = $vatEarned - $vatExpended;
            $dt->subQuarterNoOverflow();
        }
        return $this->generateEntries(
            __('quarter'), $labels, $netIncomeTaxable, $netIncomeUntaxable, $vatExpenses, $totalVat
        );
    }

    private function getYearData(): array
    {
        $labels = [];
        $netIncomeTaxable = [];
        $netIncomeUntaxable = [];
        $vatExpenses = [];
        $totalVat = [];

        $dt = Carbon::today();
        for ($i=0; $i < static::$entryCount; $i++) {
            $labels[] = $dt->year;
            [$netTaxable, $netUntaxable, $vatEarned] = Invoice::ofTime($dt, TimeUnit::YEAR);
            [, $vatExpended] = Expense::ofTime($dt, TimeUnit::YEAR);
            $netIncomeTaxable[] = $netTaxable;
            $netIncomeUntaxable[] = $netUntaxable;
            $vatExpenses[] = $vatExpended;
            $totalVat[] = $vatEarned - $vatExpended;
            $dt->subYearNoOverflow();
        }
        return $this->generateEntries(
            __('year'), $labels, $netIncomeTaxable, $netIncomeUntaxable, $vatExpenses, $totalVat
        );
    }
}
