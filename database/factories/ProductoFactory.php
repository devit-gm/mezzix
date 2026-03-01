<?php

namespace Database\Factories;

use App\Models\Producto;
use App\Models\Familia;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Producto>
 */
class ProductoFactory extends Factory
{
    protected $model = Producto::class;

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
            'precio' => $this->faker->randomFloat(2, 1, 100),
            'stock' => $this->faker->numberBetween(0, 100),
            'stock_reservado' => 0,
            'combinado' => 0,
            'familia' => Familia::factory(),
            'imagen' => 'default.jpg',
            'posicion' => $this->faker->numberBetween(1, 100),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }

    /**
     * Indicate that the product is a combined product.
     */
    public function combinado(): static
    {
        return $this->state(fn (array $attributes) => [
            'combinado' => 1,
            'precio' => 0
        ]);
    }

    /**
     * Indicate that the product is out of stock.
     */
    public function sinStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => 0,
            'stock_reservado' => 0
        ]);
    }

    /**
     * Indicate that the product has low stock.
     */
    public function stockBajo(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => 3,
            'stock_reservado' => 0
        ]);
    }
}
