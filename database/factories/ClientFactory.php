<?php

namespace Database\Factories;

use App\Enums\LanguageCode;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Client::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'short' => str($name)->take(2)->upper()->toString(),
            'color' => fake()->hexColor(),
            'address' => fake()->secondaryAddress(),
            'street' => fake()->streetAddress(),
            'zip' => fake()->postcode(),
            'city' => fake()->city(),
            'country' => fake()->country(),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'language' => fake()->randomElement(array_column(LanguageCode::cases(), 'value')),
            'vat_id' => fake()->bothify('DE#########'),
        ];
    }
}
