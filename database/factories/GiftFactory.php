<?php

namespace Database\Factories;

use App\Models\Gift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Gift>
 */
class GiftFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Gift::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'received_at' => fake()->dateTimeBetween('-2 years')->format('Y-m-d'),
            'amount' => fake()->randomFloat(2, 10, 500),
            'subject' => fake()->optional()->sentence(),
            'name' => fake()->optional()->name(),
            'email' => fake()->optional()->unique()->safeEmail(),
        ];
    }
}
