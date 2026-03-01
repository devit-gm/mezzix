<?php

namespace Database\Factories;

use App\Models\ComposicionProducto;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ComposicionProducto>
 */
class ComposicionProductoFactory extends Factory
{
    protected $model = ComposicionProducto::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'id_producto' => Producto::factory(),
            'id_componente' => Producto::factory(),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}
