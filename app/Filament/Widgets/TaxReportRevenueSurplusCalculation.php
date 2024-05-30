<?php

namespace App\Filament\Widgets;

use App\Enums\ExpenseCategory;
use App\Enums\TimeUnit;
use App\Models\Expense;
use App\Models\Invoice;
use Carbon\Carbon;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontFamily;
use Filament\Widgets\Widget;
use Illuminate\Support\Number;

class TaxReportRevenueSurplusCalculation extends Widget implements HasForms, HasInfolists
{
    use InteractsWithInfolists;
    use InteractsWithForms;

    protected int | string | array $columnSpan = 4;
    protected static string $view = 'filament.widgets.tax-report-revenue-surplus-calculation';
    // protected static ?string $maxHeight = '265px';
    // protected static int $entryCount = 6;
    public ?int $filter = null;

    public function __construct()
    {
        // set default filter to last year
        $this->filter = now()->year - 1;
    }

    public function getHeading(): string
    {
        return __('attachmentRSC');
    }

    protected function getFilters(): ?array
    {
        return Invoice::getYearList();
    }

    protected function getData(): ?array
    {
        $dt = Carbon::create($this->filter, 1, 1);
        [$netEarned, $vatEarned] = Invoice::ofTime($dt, TimeUnit::YEAR);
        [$netGoodExpended, $vatGoodExpended] = Expense::ofTime($dt, TimeUnit::YEAR, ExpenseCategory::Good);
        [$netServiceExpended, $vatServiceExpended] = Expense::ofTime($dt, TimeUnit::YEAR, ExpenseCategory::Service);
        $netExpended = $netGoodExpended + $netServiceExpended;
        $vatExpended = $vatGoodExpended + $vatServiceExpended;
        return [
            '14' => [
                'value' => $netEarned,
                'help' => 'Umsatzsteuerpflichtige Betriebseinnahmen',
            ],
            '16' => [
                'value' => $vatEarned,
                'help' => 'Vereinnahmte Umsatzsteuer',
            ],
            '26' => [
                'value' => $netGoodExpended,
                'help' => 'Waren, Rohstoffe und Hilfsstoffe',
            ],
            '27' => [
                'value' => $netServiceExpended,
                'help' => 'Bezogene Leistungen',
            ],
            '55' => [
                'value' => $vatExpended,
                'help' => 'Gezahlte Vorsteuerbeträge',
            ],
        ];
    }

    private function renderData(): array
    {
        $entries = [];
        foreach ($this->getData() as $nr => $line) {
            $entries[] = Components\TextEntry::make('label')
                ->label('')
                ->fontFamily(FontFamily::Mono)
                ->state(__('lineN', ['n' => $nr]))
                ->tooltip($line['help'])
                ->grow(false);
            $entries[] = Components\TextEntry::make("$nr.value")
                ->label('')
                ->money('eur')
                ->fontFamily(FontFamily::Mono)
                ->alignRight()
                ->copyable()
                ->copyableState(fn (string $state): string => Number::format(floatVal($state)));
        }
        return $entries;
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->state($this->getData())
            ->schema($this->renderData())->columns(2)->extraAttributes(['class' => 'data-list']);
    }
}
