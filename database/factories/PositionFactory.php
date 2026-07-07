<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Position>
 */
class PositionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Position::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = fake()->dateTimeBetween('-6 months', 'now');
        $durationHours = fake()->numberBetween(1, 8); // 1 to 8 hours
        $finishedAt = (clone $startedAt)->modify("+{$durationHours} hours");

        return [
            'invoice_id' => Invoice::factory(),
            'started_at' => $startedAt,
            'finished_at' => $finishedAt,
            'pause_duration' => fake()->optional(0.2)->randomElement([1.0, 2.0]),
            'description' => fake()->sentence(),
            'remote' => fake()->boolean(90), // 90% chance of being remote
        ];
    }

    /**
     * Indicate that the position is remote.
     */
    public function remote(): static
    {
        return $this->state(fn (array $attributes) => [
            'remote' => true,
        ]);
    }

    /**
     * Indicate that the position is on-site.
     */
    public function onSite(): static
    {
        return $this->state(fn (array $attributes) => [
            'remote' => false,
        ]);
    }

    /**
     * Create a position with a specific duration in hours.
     */
    public function withDuration(float $hours): static
    {
        return $this->state(function (array $attributes) use ($hours) {
            $startedAt = $attributes['started_at'];
            $finishedAt = (clone $startedAt)->modify("+{$hours} hours");
            return [
                'started_at' => $startedAt,
                'finished_at' => $finishedAt,
            ];
        });
    }
}
