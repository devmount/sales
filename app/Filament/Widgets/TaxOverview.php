<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\Expense;
use Filament\Widgets\Widget;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\Alignment;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Tabs;
use Carbon\Carbon;
use NumberFormatter;

class TaxOverview extends Widget implements HasForms, HasInfolists
{
    use InteractsWithInfolists;
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.tax-overview';
    protected static int $entryCount = 6;

    public function taxInfolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Tabs::make()->tabs([
                Tabs\Tab::make('perMonth')
                    ->translateLabel()
                    ->schema([ Grid::make(12)->schema($this->getMonthData()) ]),
                Tabs\Tab::make('perQuarter')
                    ->translateLabel()
                    ->schema([ Grid::make(12)->schema($this->getQuarterData()) ]),
                Tabs\Tab::make('perYear')
                    ->translateLabel()
                    ->schema([ Grid::make(12)->schema($this->getYearData()) ]),
            ]),
        ]);
    }

    private function generateEntries($labels, $netIncome, $vatExpenses, $totalVat): array
    {
        return [
            TextEntry::make('month')
                ->label(__('month'))
                ->columnSpan(3)
                ->fontFamily(FontFamily::Mono)
                ->state($labels)
                ->listWithLineBreaks(),
            TextEntry::make('netIncome')
                ->label(__('netIncome'))
                ->columnSpan(3)
                ->money('eur')
                ->fontFamily(FontFamily::Mono)
                ->state($netIncome)
                ->color(fn (string $state): string => !$state ? 'gray' : 'normal')
                ->listWithLineBreaks()
                ->copyable()
                ->copyableState(fn (string $state): string => (new NumberFormatter(app()->getLocale(), NumberFormatter::DECIMAL))->format((float)$state)),
            TextEntry::make('vatExpenses')
                ->label(__('vatExpenses'))
                ->columnSpan(3)
                ->money('eur')
                ->fontFamily(FontFamily::Mono)
                ->state($vatExpenses)
                ->color(fn (string $state): string => !$state ? 'gray' : 'normal')
                ->listWithLineBreaks()
                ->copyable()
                ->copyableState(fn (string $state): string => (new NumberFormatter(app()->getLocale(), NumberFormatter::DECIMAL))->format((float)$state)),
            TextEntry::make('totalVat')
                ->label(__('totalVat'))
                ->columnSpan(3)
                ->money('eur')
                ->fontFamily(FontFamily::Mono)
                ->state($totalVat)
                ->color(fn (string $state): string => !$state ? 'gray' : 'normal')
                ->listWithLineBreaks()
                ->copyable()
                ->copyableState(fn (string $state): string => (new NumberFormatter(app()->getLocale(), NumberFormatter::DECIMAL))->format((float)$state)),
            ];
    }

    private function getMonthData(): array
    {
        $labels = [];
        $netIncome = [];
        $vatExpenses = [];
        $totalVat = [];

        $dt = Carbon::today();
        for ($i=0; $i < static::$entryCount; $i++) {
            $labels[] = $dt->locale(app()->getLocale())->monthName;
            $invoices = Invoice::query()
                ->where('paid_at', '>=', $dt->startOfMonth()->toDateString())
                ->where('paid_at', '<=', $dt->endOfMonth()->toDateString())
                ->get();
            $netEarned = array_sum($invoices->map(fn (Invoice $i) => $i->net)->toArray());
            $vatEarned = array_sum($invoices->map(fn (Invoice $i) => $i->vat)->toArray());
            $netIncome[] = $netEarned;
            $expenses = Expense::query()
                ->where('expended_at', '>=', $dt->startOfMonth()->toDateString())
                ->where('expended_at', '<=', $dt->endOfMonth()->toDateString())
                ->where('taxable', '=', '1')
                ->get();
            $vatExpended = array_sum($expenses->map(fn (Expense $e) => $e->vat)->toArray());
            $vatExpenses[] = $vatExpended;
            $totalVat[] = $vatEarned - $vatExpended;
            $dt->subMonthsNoOverflow();
        }
        return $this->generateEntries($labels, $netIncome, $vatExpenses, $totalVat);
    }

    private function getQuarterData(): array
    {
        $labels = [];
        $netIncome = [];
        $vatExpenses = [];
        $totalVat = [];

        $dt = Carbon::today();
        for ($i=0; $i < static::$entryCount; $i++) {
            $labels[] = "$dt->year Q$dt->quarter";
            $invoices = Invoice::query()
                ->where('paid_at', '>=', $dt->startOfQuarter()->toDateString())
                ->where('paid_at', '<=', $dt->endOfQuarter()->toDateString())
                ->get();
            $netEarned = array_sum($invoices->map(fn (Invoice $i) => $i->net)->toArray());
            $vatEarned = array_sum($invoices->map(fn (Invoice $i) => $i->vat)->toArray());
            $netIncome[] = $netEarned;
            $expenses = Expense::query()
                ->where('expended_at', '>=', $dt->startOfQuarter()->toDateString())
                ->where('expended_at', '<=', $dt->endOfQuarter()->toDateString())
                ->where('taxable', '=', '1')
                ->get();
            $vatExpended = array_sum($expenses->map(fn (Expense $e) => $e->vat)->toArray());
            $vatExpenses[] = $vatExpended;
            $totalVat[] = $vatEarned - $vatExpended;
            $dt->subQuarterNoOverflow();
        }
        return $this->generateEntries($labels, $netIncome, $vatExpenses, $totalVat);
    }

    private function getYearData(): array
    {
        $labels = [];
        $netIncome = [];
        $vatExpenses = [];
        $totalVat = [];

        $dt = Carbon::today();
        for ($i=0; $i < static::$entryCount; $i++) {
            $labels[] = $dt->year;
            $invoices = Invoice::query()
                ->where('paid_at', '>=', $dt->startOfYear()->toDateString())
                ->where('paid_at', '<=', $dt->endOfYear()->toDateString())
                ->get();
            $netEarned = array_sum($invoices->map(fn (Invoice $i) => $i->net)->toArray());
            $vatEarned = array_sum($invoices->map(fn (Invoice $i) => $i->vat)->toArray());
            $netIncome[] = $netEarned;
            $expenses = Expense::query()
                ->where('expended_at', '>=', $dt->startOfYear()->toDateString())
                ->where('expended_at', '<=', $dt->endOfYear()->toDateString())
                ->where('taxable', '=', '1')
                ->get();
            $vatExpended = array_sum($expenses->map(fn (Expense $e) => $e->vat)->toArray());
            $vatExpenses[] = $vatExpended;
            $totalVat[] = $vatEarned - $vatExpended;
            $dt->subYearNoOverflow();
        }
        return $this->generateEntries($labels, $netIncome, $vatExpenses, $totalVat);
    }
}
