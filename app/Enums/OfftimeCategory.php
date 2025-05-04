<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Colors\Color;

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
            self::Vacation => Color::Lime,
            self::Holiday => Color::Lime,
            self::Sick => Color::Rose,
            self::Incident => Color::Rose,
        };
    }

    public function hexColor(): string
    {
        return match ($this) {
            self::Vacation => '#9ae600',
            self::Holiday => '#9ae600',
            self::Sick => '#ff2056',
            self::Incident => '#ff2056',
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

    public static function options(): array
    {
        return [
            self::Vacation->value => __('vacation'),
            self::Holiday->value => __('holiday'),
            self::Sick->value => __('sick'),
            self::Incident->value => __('incident'),
        ];
    }
}
