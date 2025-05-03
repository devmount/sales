<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;

enum OfftimeCategory: string implements HasLabel, HasColor
{
    case Vacation = 'vacation';
    case Holiday = 'holiday';
    case Sick = 'sick';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Vacation => __('vacation'),
            self::Holiday => __('holiday'),
            self::Sick => __('sick'),
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Vacation => 'blue',
            self::Holiday => 'teal',
            self::Sick => 'purple',
        };
    }
}
