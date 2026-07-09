<?php

namespace App\Livewire;

use Filament\Widgets\Widget;

class HeadingWidget extends Widget
{
    public string $heading = '';

    protected string $view = 'livewire.heading-widget';

    protected int | string | array $columnSpan = 12;

    protected function getViewData(): array
    {
        return [
            'heading' => $this->heading,
        ];
    }
}
