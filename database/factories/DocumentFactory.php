<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Document>
 */
class DocumentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Document::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = fake()->word() . '.pdf';

        return [
            'documentable_type' => Invoice::class,
            'documentable_id' => Invoice::factory(),
            'disk' => 'local',
            'path' => 'documents/' . fake()->uuid() . '.pdf',
            'filename' => $filename,
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(1_000, 500_000),
        ];
    }
}
