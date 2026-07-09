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
