<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;

enum OfftimeCategory: string implements HasLabel, HasColor
{
    case Vacation = 'vacation';
    case Holiday = 'holiday';
    case Sick = 'sick';
    case Incident = 'incident';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Vacation => __('vacation'),
            self::Holiday => __('holiday'),
            self::Sick => __('sick'),
            self::Incident => __('incident'),
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Vacation => 'blue',
            self::Holiday => 'teal',
            self::Sick => 'purple',
            self::Incident => 'purple',
        };
    }

    public function isPlanned(): bool
    {
        return match ($this) {
            self::Vacation => true,
            self::Holiday => true,
            self::Sick => false,
            self::Incident => false,
        };
    }
}
