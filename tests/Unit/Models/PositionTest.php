<?php

use App\Enums\PricingUnit;
use App\Models\Invoice;
use App\Models\Position;
use Carbon\Carbon;

it('has expected fillable attributes', function () {
    expect((new Position())->getFillable())->toBe([
        'started_at',
        'finished_at',
        'pause_duration',
        'description',
        'remote',
    ]);
});

it('casts attributes to their expected types', function () {
    $position = Position::factory()->create([
        'started_at' => '2026-03-01 09:00:00',
        'finished_at' => '2026-03-01 17:00:00',
        'pause_duration' => '1.5',
        'remote' => 1,
    ]);

    expect($position->started_at)->toBeInstanceOf(Illuminate\Support\Carbon::class)
        ->and($position->pause_duration)->toBeFloat()->toBe(1.5)
        ->and($position->remote)->toBeTrue();
});

it('belongs to an invoice', function () {
    $invoice = Invoice::factory()->create();
    $position = Position::factory()->create(['invoice_id' => $invoice->id]);

    expect($position->invoice)->toBeInstanceOf(Invoice::class)
        ->and($position->invoice->id)->toBe($invoice->id);
});

it('calculates duration in hours minus the pause', function () {
    $position = Position::factory()->create([
        'started_at' => '2026-03-01 09:00:00',
        'finished_at' => '2026-03-01 17:00:00',
        'pause_duration' => 1,
    ]);

    expect($position->duration)->toBe(7.0);
});

it('calculates net for an hourly invoice based on duration and price', function () {
    $invoice = Invoice::factory()->create([
        'pricing_unit' => PricingUnit::Hour,
        'price' => 100,
        'discount' => null,
    ]);
    $position = Position::factory()->create([
        'invoice_id' => $invoice->id,
        'started_at' => '2026-03-01 09:00:00',
        'finished_at' => '2026-03-01 14:00:00',
        'pause_duration' => 0,
    ]);

    expect($position->net)->toBe(500.0);
});

it('calculates net for a project-priced invoice proportional to its share of hours', function () {
    $invoice = Invoice::factory()->create([
        'pricing_unit' => PricingUnit::Project,
        'price' => 1000,
        'discount' => null,
    ]);
    $position = Position::factory()->create([
        'invoice_id' => $invoice->id,
        'started_at' => '2026-03-01 09:00:00',
        'finished_at' => '2026-03-01 14:00:00',
        'pause_duration' => 0,
    ]);
    $position->refresh();

    $expected = round($invoice->fresh()->hours / $invoice->fresh()->net * $position->duration, 2);

    expect($position->net)->toBe($expected);
});

it('formats the time range using the start and finish timestamps', function () {
    $position = Position::factory()->create([
        'started_at' => '2026-03-01 09:00:00',
        'finished_at' => '2026-03-01 17:30:00',
    ]);

    $expected = Carbon::parse($position->started_at)->isoFormat('lll')
        . Carbon::parse($position->finished_at)->format(' - H.i');

    expect($position->time_range)->toBe($expected);
});
