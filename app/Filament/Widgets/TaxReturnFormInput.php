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

class TaxReturnFormInput extends Widget implements HasForms, HasInfolists
{
    use InteractsWithInfolists;
    use InteractsWithForms;

    protected int | string | array $columnSpan = 4;
    protected static string $view = 'filament.widgets.tax-report-revenue-surplus-calculation';
    // protected static ?string $maxHeight = '265px';
    public ?int $filter = null;

    public function __construct()
    {
        // set default filter to last year
        $this->filter = now()->year - 1;
    }

    public function getHeading(): string
    {
        return __('taxReport');
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
            [
                'vr' => '22',
                'rsc' => '14',
                'value' => $netEarned,
                'help' => __('formLabels')['rsc14'],
            ],
            [
                'vr' => null,
                'rsc' => '16',
                'value' => $vatEarned,
                'help' => __('formLabels')['rsc16'],
            ],
            [
                'vr' => null,
                'rsc' => '26',
                'value' => $netGoodExpended,
                'help' => __('formLabels')['rsc26'],
            ],
            [
                'vr' => null,
                'rsc' => '27',
                'value' => $netServiceExpended,
                'help' => __('formLabels')['rsc27'],
            ],
            [
                'vr' => '79',
                'rsc' => '55',
                'value' => $vatExpended,
                'help' => __('formLabels')['rsc55'],
            ],
            [
                'vr' => '118',
                'rsc' => null,
                'value' => $vatEarned - $vatExpended,
                'help' => __('formLabels')['vr118'],
            ],
            [
                'vr' => null,
                'rsc' => '97',
                'value' => $netEarned + $vatEarned - $netExpended - $vatExpended,
                'help' => __('formLabels')['rsc97'],
                'color' => 'gray',
            ],
        ];
    }

    private function renderData(): array
    {
        $entries = [];
        foreach ($this->getData() as $key => $line) {
            // VAT return
            $entries[] = Components\TextEntry::make('vr')
                ->label($key === 0 ? __('vr') : '')
                ->fontFamily(FontFamily::Mono)
                ->state($line['vr'] ? __('lineN', ['n' => $line['vr']]) : '')
                ->color($line['color'] ?? false)
                ->grow(false);
            // Revenue Surplus calculation
            $entries[] = Components\TextEntry::make('rsc')
                ->label($key === 0 ? __('rsc') : '')
                ->fontFamily(FontFamily::Mono)
                ->state($line['rsc'] ? __('lineN', ['n' => $line['rsc']]) : '')
                ->color($line['color'] ?? false)
                ->grow(false);
            // Values
            $entries[] = Components\TextEntry::make('value')
                ->label($key === 0 ? __('value') : '')
                ->money('eur')
                ->state($line['value'])
                ->fontFamily(FontFamily::Mono)
                ->color($line['color'] ?? false)
                ->alignRight()
                ->tooltip($line['help'])
                ->copyable()
                ->copyableState(fn (string $state): string => Number::format(floatVal($state)));
        }
        return $entries;
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema($this->renderData())
            ->columns(3)
            ->extraAttributes(['class' => 'data-list']);
    }
}
