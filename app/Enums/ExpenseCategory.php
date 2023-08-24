<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;

enum ExpenseCategory: string implements HasLabel, HasColor
{
    case Vat = 'vat';
    case Good = 'good';
    case Service = 'service';
    case Tax = 'tax';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Vat => __('Vat'),
            self::Good => __('Good'),
            self::Service => __('Service'),
            self::Tax => __('Tax'),
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Vat => 'teal',
            self::Good => 'blue',
            self::Service => 'purple',
            self::Tax => 'rose',
        };
    }
}
