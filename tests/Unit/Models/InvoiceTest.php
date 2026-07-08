<?php

use App\Enums\InvoiceStatus;
use App\Enums\PricingUnit;
use App\Enums\TimeUnit;
use App\Models\Invoice;
use App\Models\Position;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Number;

it('has expected fillable attributes', function () {
    expect((new Invoice())->getFillable())->toBe([
        'title',
        'description',
        'price',
        'pricing_unit',
        'discount',
        'taxable',
        'transitory',
        'undated',
        'vat_rate',
        'invoiced_at',
        'paid_at',
        'deduction',
    ]);
});

it('casts attributes to their expected types', function () {
    $invoice = Invoice::factory()->create([
        'pricing_unit' => 'h',
        'price' => '100',
        'taxable' => 1,
        'undated' => 0,
    ]);

    expect($invoice->pricing_unit)->toBe(PricingUnit::Hour)
        ->and($invoice->price)->toBeFloat()
        ->and($invoice->taxable)->toBeTrue()
        ->and($invoice->undated)->toBeFalse();
});

it('belongs to a project and has many positions', function () {
    $project = Project::factory()->create();
    $invoice = Invoice::factory()->create(['project_id' => $project->id]);
    Position::factory()->count(2)->create(['invoice_id' => $invoice->id]);

    expect($invoice->project->id)->toBe($project->id)
        ->and($invoice->positions)->toHaveCount(2);
});

it('scopes invoices by their active, waiting and finished state', function () {
    $active = Invoice::factory()->active()->create();
    $waiting = Invoice::factory()->waiting()->create();
    $finished = Invoice::factory()->finished()->create();

    expect(Invoice::active()->pluck('id')->all())->toBe([$active->id])
        ->and(Invoice::waiting()->pluck('id')->all())->toBe([$waiting->id])
        ->and(Invoice::finished()->pluck('id')->all())->toBe([$finished->id]);
});

it('sorts positions by their starting date when the invoice is dated', function () {
    $invoice = Invoice::factory()->create(['undated' => false]);
    $later = Position::factory()->create(['invoice_id' => $invoice->id, 'started_at' => '2026-06-10 09:00:00']);
    $earlier = Position::factory()->create(['invoice_id' => $invoice->id, 'started_at' => '2026-06-01 09:00:00']);

    expect(collect($invoice->sorted_positions)->pluck('id')->all())->toBe([$earlier->id, $later->id]);
});

it('sorts positions by creation order when the invoice is undated', function () {
    $invoice = Invoice::factory()->create(['undated' => true]);
    $first = Position::factory()->create(['invoice_id' => $invoice->id, 'started_at' => '2026-06-10 09:00:00']);
    $second = Position::factory()->create(['invoice_id' => $invoice->id, 'started_at' => '2026-06-01 09:00:00']);

    expect(collect($invoice->sorted_positions)->pluck('id')->all())->toBe([$first->id, $second->id]);
});

it('paginates positions into a single page when they fit within 50 lines', function () {
    $invoice = Invoice::factory()->create();
    $first = Position::factory()->create(['invoice_id' => $invoice->id, 'started_at' => '2026-06-01 09:00:00', 'description' => 'Line 1']);
    $second = Position::factory()->create(['invoice_id' => $invoice->id, 'started_at' => '2026-06-02 09:00:00', 'description' => 'Line 1']);

    $paginated = $invoice->paginated_positions;

    expect($paginated)->toHaveCount(1)
        ->and(collect($paginated[0])->pluck('id')->all())->toBe([$first->id, $second->id]);
});

it('formats the number of positions', function () {
    $invoice = Invoice::factory()->create();
    Position::factory()->count(2)->create(['invoice_id' => $invoice->id]);

    expect($invoice->positions_formatted)->toBe('2 ' . trans_choice('position', 2));
});

it('returns the number of hours per pricing unit', function () {
    $hourly = Invoice::factory()->create(['pricing_unit' => PricingUnit::Hour]);
    $daily = Invoice::factory()->create(['pricing_unit' => PricingUnit::Day]);
    $fixed = Invoice::factory()->create(['pricing_unit' => PricingUnit::Project]);

    expect($hourly->pricing_hours)->toBe(1)
        ->and($daily->pricing_hours)->toBe(8)
        ->and($fixed->pricing_hours)->toBe(1);
});

it('sums worked hours from its positions', function () {
    $invoice = Invoice::factory()->create();
    Position::factory()->create([
        'invoice_id' => $invoice->id,
        'started_at' => '2026-03-01 09:00:00',
        'finished_at' => '2026-03-01 14:00:00',
        'pause_duration' => 0,
    ]);

    expect($invoice->hours)->toBe(5.0)
        ->and($invoice->hours_formatted)->toBe('5 ' . trans_choice('hour', 5));
});

it('calculates real net based on worked hours for hourly pricing', function () {
    $invoice = Invoice::factory()->create(['pricing_unit' => PricingUnit::Hour, 'price' => 100]);
    Position::factory()->create([
        'invoice_id' => $invoice->id,
        'started_at' => '2026-03-01 09:00:00',
        'finished_at' => '2026-03-01 14:00:00',
        'pause_duration' => 0,
    ]);

    expect($invoice->real_net)->toBe(500.0);
});

it('uses the flat price as real net for project-based pricing', function () {
    $invoice = Invoice::factory()->create(['pricing_unit' => PricingUnit::Project, 'price' => 2000]);
    Position::factory()->create([
        'invoice_id' => $invoice->id,
        'started_at' => '2026-03-01 09:00:00',
        'finished_at' => '2026-03-01 14:00:00',
        'pause_duration' => 0,
    ]);

    expect($invoice->real_net)->toBe(2000.0);
});

it('reduces the real net by the discount to calculate net', function () {
    $invoice = Invoice::factory()->create([
        'pricing_unit' => PricingUnit::Hour,
        'price' => 100,
        'discount' => 50,
    ]);
    Position::factory()->create([
        'invoice_id' => $invoice->id,
        'started_at' => '2026-03-01 09:00:00',
        'finished_at' => '2026-03-01 14:00:00',
        'pause_duration' => 0,
    ]);

    expect($invoice->net)->toBe(450.0)
        ->and($invoice->net_formatted)->toBe(Number::currency(450.0, 'eur'));
});

it('calculates vat only when the invoice is taxable', function () {
    $taxable = Invoice::factory()->create(['pricing_unit' => PricingUnit::Project, 'price' => 100, 'discount' => null, 'taxable' => true, 'vat_rate' => 0.19]);
    $untaxable = Invoice::factory()->create(['pricing_unit' => PricingUnit::Project, 'price' => 100, 'discount' => null, 'taxable' => false, 'vat_rate' => null]);

    expect($taxable->vat)->toBe(19.0)
        ->and($taxable->gross)->toBe(119.0)
        ->and($untaxable->vat)->toBe(0.0)
        ->and($untaxable->gross)->toBe(100.0);
});

it('subtracts the deduction from the gross amount to calculate the final amount', function () {
    $invoice = Invoice::factory()->create([
        'pricing_unit' => PricingUnit::Project,
        'price' => 100,
        'discount' => null,
        'taxable' => false,
        'vat_rate' => null,
        'deduction' => 15,
    ]);

    expect($invoice->final)->toBe(85.0);
});

it('formats the current invoice number using today and its id', function () {
    $invoice = Invoice::factory()->create();

    expect($invoice->current_number)->toBe(now()->format('Ymd') . str_pad((string) $invoice->id, 4, '0', STR_PAD_LEFT));
});

it('formats the final invoice number using the invoiced date and its id', function () {
    $invoice = Invoice::factory()->create(['invoiced_at' => '2026-05-01']);

    expect($invoice->final_number)->toBe('20260501' . str_pad((string) $invoice->id, 4, '0', STR_PAD_LEFT));
});

it('formats the final invoice number with an empty date prefix when not invoiced yet', function () {
    $invoice = Invoice::factory()->create(['invoiced_at' => null]);

    expect($invoice->final_number)->toBe(str_pad((string) $invoice->id, 4, '0', STR_PAD_LEFT));
});

it('derives the invoice status from its invoiced and paid dates', function () {
    $running = Invoice::factory()->create(['invoiced_at' => null, 'paid_at' => null]);
    $sent = Invoice::factory()->create(['invoiced_at' => '2026-05-01', 'paid_at' => null]);
    $paid = Invoice::factory()->create(['invoiced_at' => '2026-05-01', 'paid_at' => '2026-05-10']);
    $invalid = Invoice::factory()->make(['invoiced_at' => null, 'paid_at' => '2026-05-10']);

    expect($running->status)->toBe(InvoiceStatus::RUNNING)
        ->and($sent->status)->toBe(InvoiceStatus::SENT)
        ->and($paid->status)->toBe(InvoiceStatus::PAID)
        ->and($invalid->status)->toBe(InvoiceStatus::INVALID);
});

it('lists the years with paid, non-transitory invoices from oldest to current', function () {
    Invoice::factory()->create(['paid_at' => '2023-05-01', 'transitory' => false]);
    Invoice::factory()->create(['paid_at' => '2024-05-01', 'transitory' => false]);
    Invoice::factory()->create(['paid_at' => '2020-01-01', 'transitory' => true]);

    $expected = array_reverse(range(2023, (int) now()->format('Y')));

    expect(Invoice::getYearList())->toBe(array_combine($expected, array_map('strval', $expected)));
});

it('sums taxable and untaxable net plus vat of invoices paid within a time range', function () {
    Invoice::factory()->create([
        'paid_at' => '2026-03-05',
        'pricing_unit' => PricingUnit::Project,
        'price' => 100,
        'discount' => null,
        'taxable' => true,
        'vat_rate' => 0.19,
    ]);
    Invoice::factory()->create([
        'paid_at' => '2026-03-20',
        'pricing_unit' => PricingUnit::Project,
        'price' => 50,
        'discount' => null,
        'taxable' => false,
        'vat_rate' => null,
    ]);
    // Outside the requested month, must be excluded.
    Invoice::factory()->create([
        'paid_at' => '2026-04-01',
        'pricing_unit' => PricingUnit::Project,
        'price' => 1000,
        'discount' => null,
        'taxable' => false,
        'vat_rate' => null,
    ]);

    [$netTaxable, $netUntaxable, $vat] = Invoice::ofTime(Carbon::parse('2026-03-15'), TimeUnit::MONTH);

    expect($netTaxable)->toBe(100.0)
        ->and($netUntaxable)->toBe(50.0)
        ->and($vat)->toBe(19.0);
});
