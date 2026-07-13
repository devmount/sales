<?php

namespace Database\Factories;

use App\Enums\ExpenseCategory;
use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Expense::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $taxable = fake()->boolean(80); // 80% chance of being taxable

        return [
            'expended_at' => fake()->dateTimeBetween('-1 year')->format('Y-m-d'),
            'price' => fake()->randomFloat(2, 5, 500),
            'taxable' => $taxable,
            'vat_rate' => $taxable ? fake()->randomElement([0.07, 0.19]) : null,
            'quantity' => fake()->numberBetween(1, 10),
            'category' => fake()->randomElement(array_column(ExpenseCategory::cases(), 'value')),
            'description' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the expense is not taxable.
     */
    public function notTaxable(): static
    {
        return $this->state(fn(array $attributes) => [
            'taxable' => false,
            'vat_rate' => null,
        ]);
    }
}
