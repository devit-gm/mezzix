<?php

namespace Database\Factories;

use App\Models\Familia;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Familia>
 */
class FamiliaFactory extends Factory
{
    protected $model = Familia::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'nombre' => $this->faker->words(2, true),
            'imagen' => 'default.jpg',
            'posicion' => $this->faker->numberBetween(1, 100),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}
