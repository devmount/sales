<?php

use App\Enums\PricingUnit;
use App\Models\Client;
use App\Models\Estimate;
use App\Models\Invoice;
use App\Models\Position;
use App\Models\Project;
use App\Models\Setting;

it('has expected fillable attributes', function () {
    expect((new Project())->getFillable())->toBe([
        'title',
        'description',
        'start_at',
        'due_at',
        'minimum',
        'scope',
        'price',
        'pricing_unit',
        'aborted',
    ]);
});

it('casts attributes to their expected types', function () {
    $project = Project::factory()->create([
        'scope' => '40',
        'price' => '100',
        'pricing_unit' => 'h',
        'aborted' => 0,
    ]);

    expect($project->scope)->toBeFloat()
        ->and($project->price)->toBeFloat()
        ->and($project->pricing_unit)->toBe(PricingUnit::Hour)
        ->and($project->aborted)->toBeFalse();
});

it('belongs to a client', function () {
    $client = Client::factory()->create();
    $project = Project::factory()->create(['client_id' => $client->id]);

    expect($project->client->id)->toBe($client->id);
});

it('has many estimates and invoices', function () {
    $project = Project::factory()->create();
    Estimate::factory()->count(2)->create(['project_id' => $project->id]);
    Invoice::factory()->count(3)->create(['project_id' => $project->id]);

    expect($project->estimates)->toHaveCount(2)
        ->and($project->invoices)->toHaveCount(3);
});

it('scopes projects by their active, upcoming, finished and aborted state', function () {
    $active = Project::factory()->active()->create();
    $upcoming = Project::factory()->upcoming()->create();
    $finished = Project::factory()->finished()->create();
    $aborted = Project::factory()->aborted()->create();

    expect(Project::active()->pluck('id')->all())->toBe([$active->id])
        ->and(Project::upcoming()->pluck('id')->all())->toBe([$upcoming->id])
        ->and(Project::finished()->pluck('id')->all())->toBe([$finished->id])
        ->and(Project::aborted()->pluck('id')->all())->toBe([$aborted->id]);
});

it('sorts estimates by weight', function () {
    $project = Project::factory()->create();
    $high = Estimate::factory()->create(['project_id' => $project->id, 'weight' => 10]);
    $low = Estimate::factory()->create(['project_id' => $project->id, 'weight' => 1]);

    expect(collect($project->sorted_estimates)->pluck('id')->all())->toBe([$low->id, $high->id]);
});

it('paginates sorted estimates into chunks of 50 description lines', function () {
    $project = Project::factory()->create();
    $first = Estimate::factory()->create([
        'project_id' => $project->id,
        'weight' => 1,
        'description' => 'Line 1',
    ]);
    $second = Estimate::factory()->create([
        'project_id' => $project->id,
        'weight' => 2,
        'description' => 'Line 1',
    ]);
    $third = Estimate::factory()->create([
        'project_id' => $project->id,
        'weight' => 3,
        'description' => implode("\n", array_fill(0, 45, 'Line')),
    ]);

    $paginated = $project->paginated_estimates;

    expect($paginated)->toHaveCount(2)
        ->and(collect($paginated[0])->pluck('id')->all())->toBe([$first->id, $second->id])
        ->and(collect($paginated[1])->pluck('id')->all())->toBe([$third->id]);
});

it('returns the number of hours per pricing unit', function () {
    $hourly = Project::factory()->create(['pricing_unit' => PricingUnit::Hour]);
    $daily = Project::factory()->create(['pricing_unit' => PricingUnit::Day]);
    $fixed = Project::factory()->create(['pricing_unit' => PricingUnit::Project]);

    expect($hourly->pricing_hours)->toBe(1)
        ->and($daily->pricing_hours)->toBe(8)
        ->and($fixed->pricing_hours)->toBe(1);
});

it('sums worked hours from its invoices', function () {
    $project = Project::factory()->create();
    $invoice = Invoice::factory()->create(['project_id' => $project->id]);
    Position::factory()->create([
        'invoice_id' => $invoice->id,
        'started_at' => '2026-03-01 09:00:00',
        'finished_at' => '2026-03-01 14:00:00',
        'pause_duration' => 0,
    ]);

    expect($project->hours)->toBe(5.0)
        ->and($project->hours_with_label)->toBe('5 ' . trans_choice('hour', 5));
});

it('formats the scope as a range when minimum differs from scope', function () {
    $project = Project::factory()->create(['minimum' => 10.5, 'scope' => 50.5]);

    expect($project->scope_range)->toBe('10 - 50 ' . trans_choice('hour', 2));
});

it('formats the scope as a single value when minimum equals scope', function () {
    $project = Project::factory()->create(['minimum' => 50, 'scope' => 50]);

    expect($project->scope_range)->toBe('50 ' . trans_choice('hour', 50));
});

it('formats the price per pricing unit', function () {
    $project = Project::factory()->create(['price' => 100, 'pricing_unit' => PricingUnit::Hour]);

    expect($project->price_per_unit)->toBe('100 € / ' . trans_choice('hour', 1));
});

it('calculates progress as worked hours over scope', function () {
    $project = Project::factory()->create(['scope' => 10]);
    $invoice = Invoice::factory()->create(['project_id' => $project->id]);
    Position::factory()->create([
        'invoice_id' => $invoice->id,
        'started_at' => '2026-03-01 09:00:00',
        'finished_at' => '2026-03-01 14:00:00',
        'pause_duration' => 0,
    ]);

    expect($project->progress)->toBe(50.0)
        ->and($project->progress_percent)->toBe('50 %');
});

it('reports progress as not available when scope is zero', function () {
    $project = Project::factory()->create(['scope' => 0]);

    expect($project->progress)->toBe(0.0)
        ->and($project->progress_percent)->toBe(__('n/a'));
});

it('sums estimated hours from its estimates', function () {
    $project = Project::factory()->create();
    Estimate::factory()->create(['project_id' => $project->id, 'amount' => 5]);
    Estimate::factory()->create(['project_id' => $project->id, 'amount' => 3]);

    expect($project->estimated_hours)->toBe(8.0);
});

it('calculates estimated net, vat and gross for hourly pricing', function () {
    Setting::where('field', 'vatRate')->update(['value' => '0.19']);

    $project = Project::factory()->create(['price' => 100, 'pricing_unit' => PricingUnit::Hour]);
    Estimate::factory()->create(['project_id' => $project->id, 'amount' => 5]);
    Estimate::factory()->create(['project_id' => $project->id, 'amount' => 3]);

    expect($project->estimated_net)->toBe(800.0)
        ->and($project->estimated_vat)->toBe(152.0)
        ->and($project->estimated_gross)->toBe(952.0);
});

it('uses the flat price as estimated net for project-based pricing', function () {
    Setting::where('field', 'vatRate')->update(['value' => '0.19']);

    $project = Project::factory()->create(['price' => 5000, 'pricing_unit' => PricingUnit::Project]);
    Estimate::factory()->create(['project_id' => $project->id, 'amount' => 20]);

    expect($project->estimated_net)->toBe(5000.0);
});
