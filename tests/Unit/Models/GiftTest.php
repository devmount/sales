<?php

use App\Models\Gift;

it('has expected fillable attributes', function () {
    expect((new Gift())->getFillable())->toBe([
        'received_at',
        'amount',
        'subject',
        'name',
        'email',
    ]);
});

it('casts attributes to their expected types', function () {
    $gift = Gift::factory()->create([
        'received_at' => '2026-03-15',
        'amount' => '123.45',
    ]);

    expect($gift->received_at)->toBeInstanceOf(Illuminate\Support\Carbon::class)
        ->and($gift->received_at->toDateString())->toBe('2026-03-15')
        ->and($gift->amount)->toBeFloat()
        ->and($gift->amount)->toBe(123.45);
});
