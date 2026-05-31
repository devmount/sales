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
    case Rent = 'rent';
    case Utility = 'utility';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Vat => __('vat'),
            self::Good => __('good'),
            self::Service => __('service'),
            self::Tax => __('incomeTax'),
            self::Rent => __('rent'),
            self::Utility => __('utilityCosts'),
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Vat => 'teal',
            self::Good => 'blue',
            self::Service => 'purple',
            self::Tax => 'rose',
            self::Rent => 'amber',
            self::Utility => 'amber',
        };
    }

    public static function deliverableCategories(): array
    {
        return [
            self::Good,
            self::Service,
        ];
    }

    public static function taxCategories(): array
    {
        return [
            self::Vat,
            self::Tax,
        ];
    }

    public static function options(): array
    {
        return [
            self::Vat->value => __('vat'),
            self::Good->value => __('good'),
            self::Service->value => __('service'),
            self::Tax->value => __('incomeTax'),
            self::Rent->value => __('rent'),
            self::Utility->value => __('utilityCosts'),
        ];
    }
}
