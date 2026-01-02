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
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Widgets\Widget;
use Illuminate\Support\Number;

class TaxReturnFormInput extends Widget implements HasForms, HasInfolists, HasActions
{
    use InteractsWithActions;
    use InteractsWithInfolists;
    use InteractsWithForms;

    protected int | string | array $columnSpan = 8;
    protected string $view = 'filament.widgets.tax-report-revenue-surplus-calculation';
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
        [$netEarned, $netUntaxableEarned, $vatEarned] = Invoice::ofTime($dt, TimeUnit::YEAR); // TODO: Untaxable income
        [$netGoodExpended, $vatGoodExpended] = Expense::ofTime($dt, TimeUnit::YEAR, ExpenseCategory::Good);
        [$netServiceExpended, $vatServiceExpended] = Expense::ofTime($dt, TimeUnit::YEAR, ExpenseCategory::Service);
        $netExpended = $netGoodExpended + $netServiceExpended;
        $vatExpended = $vatGoodExpended + $vatServiceExpended;
        return [
            [
                'itr' => '1 (S)',
                'vr' => null,
                'rsc' => null,
                'value' => round($netEarned - $netExpended),
                'help' => __('formLabels')['itr1'],
                'color' => 'primary',
            ],
            [
                'itr' => null,
                'vr' => '22',
                'rsc' => '15',
                'value' => $netEarned,
                'help' => __('formLabels')['rsc14'],
                'color' => 'primary',
            ],
            [
                'itr' => null,
                'vr' => '75',
                'rsc' => null,
                'value' => $netUntaxableEarned,
                'help' => __('formLabels')['vr75'],
                'color' => 'primary',
            ],
            [
                'itr' => null,
                'vr' => null,
                'rsc' => '17',
                'value' => $vatEarned,
                'help' => __('formLabels')['rsc16'],
                'color' => 'primary',
            ],
            [
                'itr' => null,
                'vr' => null,
                'rsc' => '27',
                'value' => $netGoodExpended,
                'help' => __('formLabels')['rsc26'],
                'color' => 'danger',
            ],
            [
                'itr' => null,
                'vr' => null,
                'rsc' => '29',
                'value' => $netServiceExpended,
                'help' => __('formLabels')['rsc27'],
                'color' => 'danger',
            ],
            [
                'itr' => null,
                'vr' => '79',
                'rsc' => '57',
                'value' => $vatExpended,
                'help' => __('formLabels')['rsc55'],
                'color' => 'danger',
            ],
            [
                'itr' => null,
                'vr' => '118',
                'rsc' => null,
                'value' => $vatEarned - $vatExpended,
                'help' => __('formLabels')['vr118'],
                'color' => 'danger',
            ],
            [
                'itr' => null,
                'vr' => null,
                'rsc' => '97',
                'value' => $netEarned + $vatEarned - $netExpended - $vatExpended,
                'help' => __('formLabels')['rsc97'],
                'color' => 'gray',
            ],
        ];
    }

    /**
     * @return array<Components\TextEntry>
     */
    private function renderData(): array
    {
        $entries = [];
        $firstLine = true;
        foreach ($this->getData() as $line) {
            // Income tax return
            $entries[] = TextEntry::make('itr')
                ->label(__('itr'))
                ->hiddenLabel(!$firstLine)
                ->fontFamily(FontFamily::Mono)
                ->state($line['itr'] ? __('lineN', ['n' => $line['itr']]) : '');
            // VAT return
            $entries[] = TextEntry::make('vr')
                ->label(__('vr'))
                ->hiddenLabel(!$firstLine)
                ->fontFamily(FontFamily::Mono)
                ->state($line['vr'] ? __('lineN', ['n' => $line['vr']]) : '');
            // Revenue Surplus calculation
            $entries[] = TextEntry::make('rsc')
                ->label(__('rsc'))
                ->hiddenLabel(!$firstLine)
                ->fontFamily(FontFamily::Mono)
                ->state($line['rsc'] ? __('lineN', ['n' => $line['rsc']]) : '');
            // Values
            $entries[] = TextEntry::make('value')
                ->label(__('value'))
                ->hiddenLabel(!$firstLine)
                ->money('eur')
                ->state($line['value'])
                ->fontFamily(FontFamily::Mono)
                ->color($line['color'] ?? false)
                ->alignRight()
                ->tooltip($line['help'])
                ->copyable()
                ->copyableState(fn (string $state): string => Number::format(floatval($state)));

            $firstLine = false;
        }
        return $entries;
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components($this->renderData())
            ->columns(4)
            ->gap(false);
    }
}
