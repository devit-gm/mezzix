<?php

namespace Database\Factories;

use App\Models\Ficha;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ficha>
 */
class FichaFactory extends Factory
{
    protected $model = Ficha::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'user_id' => User::factory(),
            'tipo' => 1, // 1=Ficha normal, 4=Evento
            'estado' => 0, // 0=Abierta, 1=Cerrada
            'invitados_grupo' => 0,
            'fecha' => now(),
            'hora' => now()->format('H:i:s'),
            'precio' => 0,
            'orden' => 0,
            'numero_comensales' => 1,
            'modo' => 'ficha',
            'estado_mesa' => 'libre',
            'notificado_recordatorio_evento' => 0,
            'inscritos_actuales' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ];
    }

    /**
     * Indicate that the ficha is closed.
     */
    public function cerrada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 1
        ]);
    }

    /**
     * Indicate that the ficha is open.
     */
    public function abierta(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 0
        ]);
    }

    /**
     * Indicate that the ficha is an event.
     */
    public function evento(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => 4,
            'aforo_maximo' => 50,
            'fecha' => now()->addDays(7) // Evento en una semana
        ]);
    }
}
