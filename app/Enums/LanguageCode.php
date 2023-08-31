<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;

enum LanguageCode: string implements HasLabel, HasColor
{
    case DE = 'de';
    case EN = 'en';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DE => __('DE'),
            self::EN => __('EN'),
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::DE => 'blue',
            self::EN => 'teal',
        };
    }
}
