<?php

use App\Enums\ExpenseCategory;
use App\Enums\TimeUnit;
use App\Models\Expense;
use Carbon\Carbon;

it('has expected fillable attributes', function () {
    expect((new Expense())->getFillable())->toBe([
        'expended_at',
        'price',
        'taxable',
        'vat_rate',
        'quantity',
        'category',
        'description',
    ]);
});

it('casts attributes to their expected types', function () {
    $expense = Expense::factory()->create([
        'expended_at' => '2026-03-15',
        'price' => '19.99',
        'taxable' => 1,
        'vat_rate' => '0.19',
        'quantity' => '3',
        'category' => 'good',
    ]);

    expect($expense->expended_at)->toBeInstanceOf(Illuminate\Support\Carbon::class)
        ->and($expense->price)->toBeFloat()
        ->and($expense->taxable)->toBeTrue()
        ->and($expense->vat_rate)->toBeFloat()
        ->and($expense->quantity)->toBeInt()
        ->and($expense->category)->toBe(ExpenseCategory::Good);
});

it('calculates gross, net and vat amounts based on price, quantity and vat rate', function () {
    $expense = Expense::factory()->create([
        'price' => 100,
        'quantity' => 1,
        'taxable' => true,
        'vat_rate' => 0.19,
    ]);

    expect($expense->gross)->toBe(100.0)
        ->and($expense->net)->toBe(84.03)
        ->and($expense->vat)->toBe(15.97);
});

it('does not apply vat to the net amount when not taxable', function () {
    $expense = Expense::factory()->create([
        'price' => 50,
        'quantity' => 2,
        'taxable' => false,
        'vat_rate' => null,
    ]);

    expect($expense->gross)->toBe(100.0)
        ->and($expense->net)->toBe(100.0)
        ->and($expense->vat)->toBe(0.0);
});

it('detects whether the last advance vat expense already exists', function () {
    expect(Expense::lastAdvanceVatExists())->toBeFalse();

    $description = 'UStVA ' . now()->year . '-' . now()->subMonth()->isoFormat('MM');
    Expense::factory()->create([
        'category' => ExpenseCategory::Vat,
        'description' => $description,
    ]);

    expect(Expense::lastAdvanceVatExists())->toBeTrue();
});

it('saves the last advance vat expense derived from last months net vat', function () {
    Expense::saveLastAdvanceVat();

    $expense = Expense::where('category', ExpenseCategory::Vat->value)->sole();

    expect($expense->price)->toBe(0.0)
        ->and($expense->quantity)->toBe(1)
        ->and($expense->taxable)->toBeFalse()
        ->and($expense->description)->toBe('UStVA ' . now()->year . '-' . now()->subMonth()->isoFormat('MM'));
});

it('sums net and vat of deliverable expenses within a time range', function () {
    $month = Carbon::parse('2026-03-15');

    Expense::factory()->create([
        'expended_at' => '2026-03-01',
        'price' => 100,
        'quantity' => 1,
        'taxable' => true,
        'vat_rate' => 0.19,
        'category' => ExpenseCategory::Good,
    ]);
    Expense::factory()->create([
        'expended_at' => '2026-03-20',
        'price' => 50,
        'quantity' => 2,
        'taxable' => false,
        'vat_rate' => null,
        'category' => ExpenseCategory::Rent,
    ]);
    // Outside the requested month, must be excluded.
    Expense::factory()->create([
        'expended_at' => '2026-04-01',
        'price' => 1000,
        'quantity' => 1,
        'taxable' => false,
        'vat_rate' => null,
        'category' => ExpenseCategory::Good,
    ]);
    // Not a deliverable category, must be excluded from the default sum.
    Expense::factory()->create([
        'expended_at' => '2026-03-10',
        'price' => 500,
        'quantity' => 1,
        'taxable' => false,
        'vat_rate' => null,
        'category' => ExpenseCategory::Vat,
    ]);

    [$net, $vat] = Expense::ofTime($month, TimeUnit::MONTH);

    expect($net)->toBe(184.03)
        ->and($vat)->toBe(15.97);
});

it('sums net and vat of a single expense category within a time range', function () {
    $month = Carbon::parse('2026-03-15');

    Expense::factory()->create([
        'expended_at' => '2026-03-01',
        'price' => 100,
        'quantity' => 1,
        'taxable' => true,
        'vat_rate' => 0.19,
        'category' => ExpenseCategory::Good,
    ]);
    Expense::factory()->create([
        'expended_at' => '2026-03-05',
        'price' => 50,
        'quantity' => 1,
        'taxable' => false,
        'vat_rate' => null,
        'category' => ExpenseCategory::Rent,
    ]);

    [$net, $vat] = Expense::ofTime($month, TimeUnit::MONTH, ExpenseCategory::Rent);

    expect($net)->toBe(50.0)
        ->and($vat)->toBe(0.0);
});
