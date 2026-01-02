<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets as AppWidgets;
use Illuminate\Contracts\Support\Htmlable;

class Taxes extends BaseDashboard
{
    protected static string $routePath = 'taxes';
    protected static ?string $title = 'Steuern';
    protected static string | \BackedEnum | null $navigationIcon = 'tabler-tax-euro';

    public function getColumns(): int|array
    {
        return 12;
    }

    public function getWidgets(): array
    {
        return [
            AppWidgets\TaxOverview::class,
            AppWidgets\TaxReturnFormInput::class,
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return __('taxes');
    }
}
