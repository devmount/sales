<?php

namespace App\Filament\Widgets;

use App\Enums\ExpenseCategory;
use App\Enums\TimeUnit;
use App\Models\Expense;
use App\Models\Invoice;
use Carbon\Carbon;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components;
use Filament\Notifications\Notification;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\FontFamily;
use Filament\Widgets\Widget;
use Illuminate\Support\Number;

class TaxOverview extends Widget implements HasForms, HasInfolists, HasActions
{
    use InteractsWithInfolists;
    use InteractsWithForms;
    use InteractsWithActions;

    protected int | string | array $columnSpan = 6;
    protected static string $view = 'filament.widgets.tax-overview';
    protected static ?string $maxHeight = '265px';
    protected static int $entryCount = 6;
    public ?string $filter = 'm';

    public function getHeading(): string
    {
        return __('vatTax');
    }

    public function taxInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->name('taxOverview')
            ->schema([
                match($this->filter) {
                    'm' => Components\Grid::make(12)->schema($this->getMonthData()),
                    'q' => Components\Grid::make(12)->schema($this->getQuarterData()),
                    'y' => Components\Grid::make(12)->schema($this->getYearData()),
                }
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

    private function generateEntries($heading, $labels, $netIncome, $vatExpenses, $totalVat): array
    {
        return [
            Components\TextEntry::make('timeUnit')
                ->label($heading)
                ->columnSpan(3)
                ->fontFamily(FontFamily::Mono)
                ->state($labels)
                ->listWithLineBreaks(),
            Components\TextEntry::make('netIncome')
                ->label(__('netIncome'))
                ->columnSpan(3)
                ->money('eur')
                ->fontFamily(FontFamily::Mono)
                ->state($netIncome)
                ->color(fn (string $state): string => !$state ? 'gray' : 'normal')
                ->listWithLineBreaks()
                ->alignRight()
                ->copyable()
                ->copyableState(fn (string $state): string => Number::format(floatVal($state))),
            Components\TextEntry::make('vatExpenses')
                ->label(__('vatExpenses'))
                ->columnSpan(3)
                ->money('eur')
                ->fontFamily(FontFamily::Mono)
                ->state($vatExpenses)
                ->color(fn (string $state): string => !$state ? 'gray' : 'normal')
                ->listWithLineBreaks()
                ->alignRight()
                ->copyable()
                ->copyableState(fn (string $state): string => Number::format(floatVal($state))),
            Components\TextEntry::make('totalVat')
                ->label(__('totalVat'))
                ->columnSpan(3)
                ->money('eur')
                ->fontFamily(FontFamily::Mono)
                ->state($totalVat)
                ->color(fn (string $state): string => !$state ? 'gray' : 'normal')
                ->listWithLineBreaks()
                ->alignRight()
                ->copyable()
                ->copyableState(fn (string $state): string => Number::format(floatVal($state))),
            Components\Actions::make([
                Components\Actions\Action::make('add_latest_vat_expense')
                    ->label(__('createLatestVatExpense'))
                    ->icon('tabler-credit-card')
                    ->size(ActionSize::ExtraSmall)
                    ->outlined()
                    // ->form([
                    //     FormComponents\DatePicker::make('expended_at')
                    //         ->label(__('expendedAt'))
                    //         ->weekStartsOnMonday()
                    //         ->required()
                    //         ->default(now())
                    //         ->suffixIcon('tabler-calendar-dollar'),
                    //     FormComponents\Textarea::make('description')
                    //         ->label(__('description'))
                    //         ->default('UStVA ' . now()->year . '-' . now()->subMonth()->isoFormat('MM'))
                    //         ->maxLength(65535)
                    // ])
                    // ->action(function (array $data) use ($totalVat): void {
                    ->action(function () use ($totalVat): void {
                        $obj = new Expense([
                            'expended_at' => now(),
                            'category' => ExpenseCategory::Vat,
                            'price' => $totalVat[1],
                            'quantity' => 1,
                            'taxable' => false,
                            'vat_rate' => 0,
                            'description' => 'UStVA ' . now()->year . '-' . now()->subMonth()->isoFormat('MM'),
                        ]);
                        $obj->save();
                        Notification::make()
                            ->title(__('vatExpenseCreated'))
                            ->success()
                            ->send();
                        redirect('/expenses?activeTab=tax');
                    })
                    // ->modalHeading('Create VAT expense')
                    // ->modalSubmitActionLabel(__('create))
                ])
                ->columnSpanFull()
                ->alignRight()
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
            [$netEarned, $vatEarned] = Invoice::ofTime($dt, TimeUnit::MONTH);
            [, $vatExpended] = Expense::ofTime($dt, TimeUnit::MONTH);
            $netIncome[] = $netEarned;
            $vatExpenses[] = $vatExpended;
            $totalVat[] = $vatEarned - $vatExpended;
            $dt->subMonthsNoOverflow();
        }
        return $this->generateEntries(__('month'), $labels, $netIncome, $vatExpenses, $totalVat);
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
            [$netEarned, $vatEarned] = Invoice::ofTime($dt, TimeUnit::QUARTER);
            [, $vatExpended] = Expense::ofTime($dt, TimeUnit::QUARTER);
            $netIncome[] = $netEarned;
            $vatExpenses[] = $vatExpended;
            $totalVat[] = $vatEarned - $vatExpended;
            $dt->subQuarterNoOverflow();
        }
        return $this->generateEntries(__('quarter'), $labels, $netIncome, $vatExpenses, $totalVat);
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
            [$netEarned, $vatEarned] = Invoice::ofTime($dt, TimeUnit::YEAR);
            [, $vatExpended] = Expense::ofTime($dt, TimeUnit::YEAR);
            $netIncome[] = $netEarned;
            $vatExpenses[] = $vatExpended;
            $totalVat[] = $vatEarned - $vatExpended;
            $dt->subYearNoOverflow();
        }
        return $this->generateEntries(__('year'), $labels, $netIncome, $vatExpenses, $totalVat);
    }
}
