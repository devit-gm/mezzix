<?php

namespace Database\Factories;

use App\Models\Servicio;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Servicio>
 */
class ServicioFactory extends Factory
{
    protected $model = Servicio::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'nombre' => $this->faker->words(3, true),
            'precio' => $this->faker->randomFloat(2, 5, 200),
            'posicion' => $this->faker->numberBetween(1, 100),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}
