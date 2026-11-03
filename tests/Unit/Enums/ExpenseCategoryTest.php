<?php

use App\Enums\ExpenseCategory;
use Filament\Support\Contracts\HasDescription;

it('implements the filament description contract', function () {
    expect(ExpenseCategory::Good)->toBeInstanceOf(HasDescription::class);
});

it('has a translated description for every category', function (string $locale) {
    app()->setLocale($locale);

    foreach (ExpenseCategory::cases() as $category) {
        expect($category->getDescription())
            ->toBeString()
            ->not->toBeEmpty()
            ->not->toEndWith('Description');
    }
})->with(['en', 'de']);

it('includes the configured minor assets net limit in its description', function () {
    config(['business.minor_assets.max_net' => 1000]);

    expect(ExpenseCategory::MinorAssets->getDescription())->toContain('1000');
});
