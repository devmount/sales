<?php

use App\Enums\PricingUnit;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Position;
use App\Models\Project;

it('has expected fillable attributes', function () {
    expect((new Client())->getFillable())->toBe([
        'name',
        'short',
        'color',
        'address',
        'street',
        'zip',
        'city',
        'country',
        'email',
        'phone',
        'language',
        'vat_id',
    ]);
});

it('casts attributes to their expected types', function () {
    $client = Client::factory()->create();

    expect($client->name)->toBeString()
        ->and($client->created_at)->toBeInstanceOf(Illuminate\Support\Carbon::class);
});

it('has many projects', function () {
    $client = Client::factory()->create();
    Project::factory()->count(2)->create(['client_id' => $client->id]);

    expect($client->projects)->toHaveCount(2);
});

it('has many invoices through its projects', function () {
    $client = Client::factory()->create();
    $project = Project::factory()->create(['client_id' => $client->id]);
    Invoice::factory()->count(3)->create(['project_id' => $project->id]);

    expect($client->invoices)->toHaveCount(3);
});

it('builds the full address including an optional address line', function () {
    $client = Client::factory()->create([
        'address' => 'Suite 100',
        'street' => 'Main St 1',
        'zip' => '12345',
        'city' => 'Berlin',
    ]);

    expect($client->full_address)->toBe("Suite 100\nMain St 1\n12345 Berlin");
});

it('builds the full address without an address line when absent', function () {
    $client = Client::factory()->create([
        'address' => null,
        'street' => 'Main St 1',
        'zip' => '12345',
        'city' => 'Berlin',
    ]);

    expect($client->full_address)->toBe("Main St 1\n12345 Berlin");
});

it('sums worked hours across all its projects, invoices and positions', function () {
    $client = Client::factory()->create();
    $project = Project::factory()->create(['client_id' => $client->id]);
    $invoiceOne = Invoice::factory()->create(['project_id' => $project->id]);
    $invoiceTwo = Invoice::factory()->create(['project_id' => $project->id]);
    Position::factory()->create([
        'invoice_id' => $invoiceOne->id,
        'started_at' => '2026-03-01 09:00:00',
        'finished_at' => '2026-03-01 14:00:00',
        'pause_duration' => 0,
    ]);
    Position::factory()->create([
        'invoice_id' => $invoiceTwo->id,
        'started_at' => '2026-03-02 09:00:00',
        'finished_at' => '2026-03-02 12:00:00',
        'pause_duration' => 0,
    ]);

    expect($client->hours)->toBe(8.0);
});

it('sums the net amount earned across all its projects and invoices', function () {
    $client = Client::factory()->create();
    $project = Project::factory()->create(['client_id' => $client->id]);
    $invoiceOne = Invoice::factory()->create([
        'project_id' => $project->id,
        'pricing_unit' => PricingUnit::Hour,
        'price' => 100,
        'discount' => null,
    ]);
    $invoiceTwo = Invoice::factory()->create([
        'project_id' => $project->id,
        'pricing_unit' => PricingUnit::Hour,
        'price' => 100,
        'discount' => null,
    ]);
    Position::factory()->create([
        'invoice_id' => $invoiceOne->id,
        'started_at' => '2026-03-01 09:00:00',
        'finished_at' => '2026-03-01 14:00:00',
        'pause_duration' => 0,
    ]);
    Position::factory()->create([
        'invoice_id' => $invoiceTwo->id,
        'started_at' => '2026-03-02 09:00:00',
        'finished_at' => '2026-03-02 12:00:00',
        'pause_duration' => 0,
    ]);

    expect($client->net)->toBe(800.0);
});

it('calculates the average payment delay across paid invoices', function () {
    $client = Client::factory()->create();
    $project = Project::factory()->create(['client_id' => $client->id]);
    Invoice::factory()->create([
        'project_id' => $project->id,
        'invoiced_at' => '2026-01-01',
        'paid_at' => '2026-01-11',
    ]);
    Invoice::factory()->create([
        'project_id' => $project->id,
        'invoiced_at' => '2026-02-01',
        'paid_at' => '2026-02-06',
    ]);

    expect($client->avg_payment_delay)->toBe(7.5);
});

it('returns zero average payment delay when no invoice is paid', function () {
    $client = Client::factory()->create();
    $project = Project::factory()->create(['client_id' => $client->id]);
    Invoice::factory()->create([
        'project_id' => $project->id,
        'invoiced_at' => null,
        'paid_at' => null,
    ]);

    expect($client->avg_payment_delay)->toBe(0.0);
});
