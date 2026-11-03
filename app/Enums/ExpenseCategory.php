<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;

enum ExpenseCategory: string implements HasLabel, HasColor, HasDescription
{
    case Vat = 'vat';
    case Good = 'good';
    case MinorAssets = 'minor_assets';
    case Service = 'service';
    case Tax = 'tax';
    case Rent = 'rent';
    case Utility = 'utility';
    case Edv = 'edv';
    case WorkEquipment = 'work_equipment';
    case Advertising = 'advertising';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Vat => __('vat'),
            self::Good => __('good'),
            self::MinorAssets => __('minorAssets'),
            self::Service => __('service'),
            self::Tax => __('incomeTax'),
            self::Rent => __('rent'),
            self::Utility => __('utilityCosts'),
            self::Edv => __('edv'),
            self::WorkEquipment => __('workEquipment'),
            self::Advertising => __('advertising'),
        };
    }

    public function getDescription(): ?string
    {
        return match ($this) {
            self::Vat => __('vatDescription'),
            self::Good => __('goodDescription'),
            self::MinorAssets => __('minorAssetsDescription', ['max' => config('business.minor_assets.max_net')]),
            self::Service => __('serviceDescription'),
            self::Tax => __('incomeTaxDescription'),
            self::Rent => __('rentDescription'),
            self::Utility => __('utilityCostsDescription'),
            self::Edv => __('edvDescription'),
            self::WorkEquipment => __('workEquipmentDescription'),
            self::Advertising => __('advertisingDescription'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Vat => 'teal',
            self::Good => 'blue',
            self::MinorAssets => 'indigo',
            self::Service => 'purple',
            self::Tax => 'rose',
            self::Rent => 'amber',
            self::Utility => 'amber',
            self::Edv => 'cyan',
            self::WorkEquipment => 'lime',
            self::Advertising => 'fuchsia',
        };
    }

    public static function deliverableCategories(): array
    {
        return [
            self::Good,
            self::MinorAssets,
            self::Service,
            self::Rent,
            self::Utility,
            self::Edv,
            self::WorkEquipment,
            self::Advertising,
        ];
    }

    public static function taxCategories(): array
    {
        return [
            self::Vat,
            self::Tax,
        ];
    }

    public function isTax(): bool
    {
        return in_array($this, self::taxCategories(), true);
    }

    public static function options(): array
    {
        return [
            self::Vat->value => __('vat'),
            self::Good->value => __('good'),
            self::MinorAssets->value => __('minorAssets'),
            self::Service->value => __('service'),
            self::Tax->value => __('incomeTax'),
            self::Rent->value => __('rent'),
            self::Utility->value => __('utilityCosts'),
            self::Edv->value => __('edv'),
            self::WorkEquipment->value => __('workEquipment'),
            self::Advertising->value => __('advertising'),
        ];
    }
}
