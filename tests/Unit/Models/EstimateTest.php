<?php

use App\Models\Estimate;
use App\Models\Project;

it('has expected fillable attributes', function () {
    expect((new Estimate())->getFillable())->toBe([
        'title',
        'description',
        'amount',
        'weight',
    ]);
});

it('casts attributes to their expected types', function () {
    $estimate = Estimate::factory()->create([
        'amount' => '42.50',
        'weight' => '5',
    ]);

    expect($estimate->amount)->toBeFloat()->toBe(42.5)
        ->and($estimate->weight)->toBeInt()->toBe(5);
});

it('belongs to a project', function () {
    $project = Project::factory()->create();
    $estimate = Estimate::factory()->create(['project_id' => $project->id]);

    expect($estimate->project)->toBeInstanceOf(Project::class)
        ->and($estimate->project->id)->toBe($project->id);
});

it('calculates net for an hourly project based on amount and price', function () {
    $project = Project::factory()->hourly()->create(['price' => 100]);
    $estimate = Estimate::factory()->create(['project_id' => $project->id, 'amount' => 5]);

    expect($estimate->net)->toBe(500.0);
});

it('calculates net for a day-priced project by dividing the day rate across its pricing hours', function () {
    $project = Project::factory()->daily()->create(['price' => 800]);
    $estimate = Estimate::factory()->create(['project_id' => $project->id, 'amount' => 4]);

    // 4 hours estimated, day rate split across 8 pricing hours: 800 / 8 * 4
    expect($estimate->net)->toBe(400.0);
});

it('calculates net for a project-priced project proportional to its share of hours', function () {
    $project = Project::factory()->project()->create(['price' => 3000]);
    $shorter = Estimate::factory()->create(['project_id' => $project->id, 'amount' => 10]);
    $longer = Estimate::factory()->create(['project_id' => $project->id, 'amount' => 20]);

    // total estimated hours: 10 + 20 = 30, flat project net: 3000 -> 100 €/hour
    expect($shorter->net)->toBe(1000.0)
        ->and($longer->net)->toBe(2000.0);
});
