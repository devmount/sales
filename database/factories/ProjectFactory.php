<?php

namespace Database\Factories;

use App\Enums\PricingUnit;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Project::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startAt = fake()->dateTimeBetween('-6 months', '+6 months')->format('Y-m-d');
        $dueAt = fake()->dateTimeBetween($startAt, '+12 months')->format('Y-m-d');

        return [
            'title' => fake()->sentence(3),
            'description' => fake()->optional(0.5)->paragraph(),
            'client_id' => Client::factory(),
            'start_at' => $startAt,
            'due_at' => $dueAt,
            'minimum' => fake()->optional(0.5)->randomFloat(2, 10, 100),
            'scope' => fake()->randomFloat(2, 20, 200),
            'price' => fake()->randomFloat(2, 50, 150),
            'pricing_unit' => PricingUnit::Hour,
            'aborted' => false,
        ];
    }

    /**
     * Indicate that the project is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_at' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'due_at' => fake()->dateTimeBetween('now', '+3 months')->format('Y-m-d'),
            'aborted' => false,
        ]);
    }

    /**
     * Indicate that the project is upcoming.
     */
    public function upcoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_at' => fake()->dateTimeBetween('now', '+2 months')->format('Y-m-d'),
            'due_at' => fake()->dateTimeBetween('+6 months', '+12 months')->format('Y-m-d'),
            'aborted' => false,
        ]);
    }

    /**
     * Indicate that the project is finished.
     */
    public function finished(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_at' => fake()->dateTimeBetween('-12 months', '-6 months')->format('Y-m-d'),
            'due_at' => fake()->dateTimeBetween('-4 months', 'now')->format('Y-m-d'),
            'aborted' => false,
        ]);
    }

    /**
     * Indicate that the project is aborted.
     */
    public function aborted(): static
    {
        return $this->state(fn (array $attributes) => [
            'aborted' => true,
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
