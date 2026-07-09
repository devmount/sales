<?php

namespace App\Filament\Resources\ProjectResource\Widgets;

use App\Models\Project;
use Filament\Widgets\Widget;

class CurrentProjects extends Widget
{
    protected ?string $pollingInterval = null;

    protected string $view = 'filament.widgets.current-projects-widget';

    protected int | string | array $columnSpan = [
        'sm' => 12,
        'xl' => 6,
    ];

    protected function getViewData(): array
    {
        return [
            'heading' => 'Current Projects',
            'description' => 'Progress of active projects',
            'projects' => Project::active()
                ->withMax('invoices', 'created_at')
                ->orderByDesc('invoices_max_created_at')
                ->get(),
        ];
    }
}
