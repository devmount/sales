<?php

namespace Database\Factories;

use App\Enums\OfftimeCategory;
use App\Models\Offtime;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Offtime>
 */
class OfftimeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Offtime::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-1 year', '+1 year');
        $end = fake()->dateTimeBetween($start, (clone $start)->modify('+5 days'));

        return [
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
            'category' => fake()->randomElement(array_column(OfftimeCategory::cases(), 'value')),
            'description' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate a single-day offtime (no end date).
     */
    public function singleDay(): static
    {
        return $this->state(fn (array $attributes) => [
            'end' => null,
        ]);
    }

    /**
     * Indicate a vacation period.
     */
    public function vacation(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => OfftimeCategory::Vacation->value,
        ]);
    }

    /**
     * Indicate a sick leave.
     */
    public function sick(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => OfftimeCategory::Sick->value,
        ]);
    }
}
