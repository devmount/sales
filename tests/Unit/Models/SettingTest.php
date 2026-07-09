<?php

use App\Models\Setting;

it('has expected fillable attributes', function () {
    expect((new Setting())->getFillable())->toBe([
        'field',
        'value',
        'type',
        'attributes',
        'weight',
    ]);
});

it('uses field as non-incrementing primary key', function () {
    $setting = new Setting();

    expect($setting->getKeyName())->toBe('field')
        ->and($setting->incrementing)->toBeFalse();
});

it('casts attributes to their expected types', function () {
    $setting = Setting::where('field', 'vatRate')->first();
    $setting->update(['attributes' => ['min' => 0, 'max' => 1]]);
    $setting->refresh();

    expect($setting->weight)->toBeInt()
        ->and($setting->attributes)->toBe(['min' => 0, 'max' => 1]);
});

it('gets the value of a setting by field', function () {
    Setting::where('field', 'name')->update(['value' => 'Acme Inc.']);

    expect(Setting::get('name'))->toBe('Acme Inc.')
        ->and(Setting::get('doesNotExist'))->toBeNull();
});

it('returns the translated label for a setting', function () {
    $setting = Setting::where('field', 'vatRate')->first();

    expect($setting->label)->toBe(__('vatRate'));
});

it('builds the company address from settings', function () {
    Setting::where('field', 'name')->update(['value' => 'Acme Inc.']);
    Setting::where('field', 'street')->update(['value' => 'Main Street 1']);
    Setting::where('field', 'zip')->update(['value' => '12345']);
    Setting::where('field', 'city')->update(['value' => 'Berlin']);

    expect(Setting::address())->toBe('Acme Inc., Main Street 1, 12345 Berlin');
});
