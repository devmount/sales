<?php

namespace Database\Factories;

use App\Enums\PricingUnit;
use App\Models\Invoice;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Invoice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $taxable = fake()->boolean(90); // 90% chance of being taxable
        $invoicedAt = fake()->optional(0.6)->dateTimeBetween('-6 months', 'now')->format('Y-m-d');
        $paidAt = $invoicedAt && fake()->boolean(70) ? fake()->dateTimeBetween($invoicedAt, '+14 days')->format('Y-m-d') : null;

        return [
            'title' => fake()->sentence(3),
            'description' => fake()->optional(0.4)->paragraph(),
            'project_id' => Project::factory(),
            'price' => fake()->randomFloat(2, 50, 150),
            'pricing_unit' => PricingUnit::Hour,
            'discount' => fake()->optional(0.1)->randomFloat(2, 10, 500),
            'taxable' => $taxable,
            'transitory' => fake()->boolean(10), // 10% chance
            'undated' => fake()->boolean(5), // 5% chance
            'vat_rate' => $taxable ? fake()->randomElement([0.07, 0.19]) : null,
            'invoiced_at' => $invoicedAt,
            'paid_at' => $paidAt,
            'deduction' => fake()->optional(0.1)->randomFloat(2, 10, 200),
        ];
    }

    /**
     * Indicate that the invoice is active (not invoiced or paid).
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'invoiced_at' => null,
            'paid_at' => null,
        ]);
    }

    /**
     * Indicate that the invoice is waiting for payment.
     */
    public function waiting(): static
    {
        return $this->state(fn (array $attributes) => [
            'invoiced_at' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'paid_at' => null,
        ]);
    }

    /**
     * Indicate that the invoice is finished (paid).
     */
    public function finished(): static
    {
        $invoicedAt = fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d');
        return $this->state(fn (array $attributes) => [
            'invoiced_at' => $invoicedAt,
            'paid_at' => fake()->dateTimeBetween($invoicedAt, '+7 days')->format('Y-m-d'),
        ]);
    }

    /**
     * Indicate that the invoice is taxable.
     */
    public function taxable(): static
    {
        return $this->state(fn (array $attributes) => [
            'taxable' => true,
            'vat_rate' => fake()->randomElement([0.07, 0.19]),
        ]);
    }

    /**
     * Indicate that the invoice is not taxable.
     */
    public function notTaxable(): static
    {
        return $this->state(fn (array $attributes) => [
            'taxable' => false,
            'vat_rate' => null,
        ]);
    }

    /**
     * Set pricing unit to hourly.
     */
    public function hourly(): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => fake()->randomFloat(2, 50, 150),
            'pricing_unit' => PricingUnit::Hour->value,
        ]);
    }

    /**
     * Set pricing unit to daily.
     */
    public function daily(): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => fake()->randomFloat(2, 450, 1250),
            'pricing_unit' => PricingUnit::Day->value,
        ]);
    }

    /**
     * Set pricing unit to project-based.
     */
    public function project(): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => fake()->randomFloat(2, 500, 15000),
            'pricing_unit' => PricingUnit::Project->value,
        ]);
    }
}
