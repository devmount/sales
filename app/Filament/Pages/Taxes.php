<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Resources\PositionResource\Widgets as ResourceWidgets;
use App\Filament\Widgets as AppWidgets;
use Illuminate\Contracts\Support\Htmlable;

class Taxes extends BaseDashboard
{
    protected static string $routePath = 'taxes';
    protected static ?string $title = 'Steuern';
    protected static ?string $navigationIcon = 'tabler-moneybag';

    public function getColumns(): int | string | array
    {
        return 12;
    }

    public function getWidgets(): array
    {
        return [
            AppWidgets\TaxOverview::class,
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return __('taxes');
    }
}
