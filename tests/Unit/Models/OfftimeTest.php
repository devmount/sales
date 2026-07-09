<?php

use App\Enums\OfftimeCategory;
use App\Models\Offtime;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

it('has expected fillable attributes', function () {
    expect((new Offtime())->getFillable())->toBe([
        'start',
        'end',
        'category',
        'description',
    ]);
});

it('casts attributes to their expected types', function () {
    $offtime = Offtime::factory()->create([
        'start' => '2026-06-15',
        'category' => 'vacation',
    ]);

    expect($offtime->start)->toBeInstanceOf(Illuminate\Support\Carbon::class)
        ->and($offtime->category)->toBe(OfftimeCategory::Vacation);
});

it('derives the year from the start date', function () {
    $offtime = Offtime::factory()->create(['start' => '2026-06-15']);

    expect($offtime->year)->toBe(2026);
});

it('counts a single day offtime as one day', function () {
    $offtime = Offtime::factory()->singleDay()->create(['start' => '2026-06-15']);

    expect($offtime->days_count)->toBe(1);
});

it('counts the inclusive number of days for a multi-day offtime', function () {
    $offtime = Offtime::factory()->create(['start' => '2026-06-01', 'end' => '2026-06-05']);

    expect($offtime->days_count)->toBe(5);
});

it('finds an offtime by a date within its range', function () {
    $offtime = Offtime::factory()->create(['start' => '2026-06-10', 'end' => '2026-06-12']);

    expect(Offtime::byDate(Carbon::parse('2026-06-10'))->id)->toBe($offtime->id)
        ->and(Offtime::byDate(Carbon::parse('2026-06-11'))->id)->toBe($offtime->id)
        ->and(Offtime::byDate(Carbon::parse('2026-06-12'))->id)->toBe($offtime->id)
        ->and(Offtime::byDate(Carbon::parse('2026-06-09')))->toBeNull();
});

it('finds a single day offtime by its exact date', function () {
    $offtime = Offtime::factory()->singleDay()->create(['start' => '2026-07-01']);

    expect(Offtime::byDate(Carbon::parse('2026-07-01'))->id)->toBe($offtime->id);
});

it('counts weekends, planned and unplanned days off for a year', function () {
    $year = 2026;
    $plannedDay = Carbon::create($year, 6, 1)->next(Carbon::MONDAY);
    $unplannedDay = $plannedDay->copy()->addDay();

    Offtime::factory()->singleDay()->vacation()->create(['start' => $plannedDay->toDateString()]);
    Offtime::factory()->singleDay()->sick()->create(['start' => $unplannedDay->toDateString()]);

    $expectedWeekends = 0;
    foreach (CarbonPeriod::create(Carbon::create($year, 1, 1), Carbon::create($year, 12, 31)) as $date) {
        if ($date->isWeekend()) {
            $expectedWeekends++;
        }
    }

    [$weekends, $planned, $unplanned, $total] = Offtime::daysCountByYear($year);

    expect($weekends)->toBe($expectedWeekends)
        ->and($planned)->toBe(1)
        ->and($unplanned)->toBe(1)
        ->and($total)->toBe($expectedWeekends + 2);
});
