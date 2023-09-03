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

class TaxOverview extends Widget implements HasForms, HasInfolists
{
    use InteractsWithInfolists;
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.tax-overview';

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

    private function getMonthData(): array
    {
        $labels = [];
        $netIncome = [];
        $vatExpenses = [];
        $totalVat = [];

        $dt = Carbon::today();
        $n = 6;
        for ($i=0; $i < $n; $i++) {
            $labels[] = $dt->locale(app()->getLocale())->monthName;
            $invoices = Invoice::query()
                ->where('paid_at', '>=', $dt->startOfMonth()->toDateString())
                ->where('paid_at', '<=', $dt->endOfMonth()->toDateString())
                ->get();
            $netEarned = array_sum($invoices->map(fn (Invoice $i) => $i->net)->toArray());
            $netIncome[] = $netEarned;
            $expenses = Expense::query()
                ->where('expended_at', '>=', $dt->startOfMonth()->toDateString())
                ->where('expended_at', '<=', $dt->endOfMonth()->toDateString())
                ->where('taxable', '=', '1')
                ->get();
            $vatExpended = array_sum($expenses->map(fn (Expense $e) => $e->price*$e->vat_rate)->toArray());
            $vatExpenses[] = $vatExpended;
            $totalVat[] = $netEarned*0.19 - $vatExpended;
            $dt->subMonthsNoOverflow();
        }
        return [
            TextEntry::make('month')
                ->label(__('month'))
                ->columnSpan(3)
                ->fontFamily(FontFamily::Mono)
                // ->alignment(Alignment::End)
                ->state($labels)
                ->listWithLineBreaks(),
            TextEntry::make('netIncome')
                ->label(__('netIncome'))
                ->columnSpan(3)
                ->money('eur')
                ->fontFamily(FontFamily::Mono)
                // ->alignment(Alignment::End)
                ->state($netIncome)
                ->listWithLineBreaks()
                ->copyable(),
            TextEntry::make('vatExpenses')
                ->label(__('vatExpenses'))
                ->columnSpan(3)
                ->money('eur')
                ->fontFamily(FontFamily::Mono)
                // ->alignment(Alignment::End)
                ->state($vatExpenses)
                ->listWithLineBreaks()
                ->copyable(),
            TextEntry::make('totalVat')
                ->label(__('totalVat'))
                ->columnSpan(3)
                ->money('eur')
                ->fontFamily(FontFamily::Mono)
                // ->alignment(Alignment::End)
                ->state($totalVat)
                ->listWithLineBreaks()
                ->copyable(),
        ];
    }

    private function getQuarterData(): array
    {
        return [];
    }

    private function getYearData(): array
    {
        return [];
    }
}
