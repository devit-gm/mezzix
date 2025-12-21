<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Ficha;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Services\FirebaseService;

class NotificarOrganizadorEvento implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * UUID de la ficha/evento
     */
    public $fichaUuid;

    /**
     * ID del usuario que se apuntó/borró
     */
    public $usuarioId;

    /**
     * Tipo de acción: 'inscripcion' o 'cancelacion'
     */
    public $accion;

    /**
     * Número de intentos
     */
    public $tries = 3;

    /**
     * Timeout en segundos
     */
    public $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(string $fichaUuid, int $usuarioId, string $accion)
    {
        $this->fichaUuid = $fichaUuid;
        $this->usuarioId = $usuarioId;
        $this->accion = $accion;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Job NotificarOrganizadorEvento iniciado', [
            'ficha_uuid' => $this->fichaUuid,
            'usuario_id' => $this->usuarioId,
            'accion' => $this->accion
        ]);

        try {
            $ficha = Ficha::find($this->fichaUuid);
            if (!$ficha) {
                Log::warning('Ficha no encontrada', ['uuid' => $this->fichaUuid]);
                return;
            }

            // Verificar que sea un evento (tipo 4)
            if ($ficha->tipo != 4) {
                Log::info('La ficha no es un evento, no se envía notificación', [
                    'tipo' => $ficha->tipo
                ]);
                return;
            }

            $organizador = $ficha->user; // El creador de la ficha
            if (!$organizador) {
                Log::warning('Organizador no encontrado', ['ficha_uuid' => $this->fichaUuid]);
                return;
            }

            $usuario = User::find($this->usuarioId);
            if (!$usuario) {
                Log::warning('Usuario no encontrado', ['usuario_id' => $this->usuarioId]);
                return;
            }

            // Contar total de inscritos
            $totalInscritos = $ficha->inscritos()->count();

            // Preparar mensaje
            $icono = $this->accion === 'inscripcion' ? '✅' : '❌';
            $verbo = $this->accion === 'inscripcion' ? 'se ha apuntado a' : 'canceló su asistencia a';
            
            $titulo = "Actualización de evento";
            $mensaje = "{$icono} {$usuario->name} {$verbo} {$ficha->descripcion}\n\nTotal de asistentes: {$totalInscritos}";
            $detalle = "Total de asistentes: {$totalInscritos}";

            // Enviar notificación push vía Firebase
            if ($organizador->fcm_token) {
                $firebaseService = app(FirebaseService::class);
                $enviado = $firebaseService->sendNotification(
                    $organizador->fcm_token,
                    $titulo,
                    $mensaje,
                    [
                        'tipo' => 'evento_actualizado',
                        'ficha_uuid' => $this->fichaUuid,
                        'accion' => $this->accion,
                        'usuario_nombre' => $usuario->name,
                        'total_inscritos' => $totalInscritos,
                        'detalle' => $detalle
                    ]
                );

                if ($enviado) {
                    Log::info('Notificación Firebase enviada correctamente', [
                        'organizador_id' => $organizador->id,
                        'ficha_uuid' => $this->fichaUuid,
                        'accion' => $this->accion
                    ]);
                } else {
                    Log::warning('No se pudo enviar notificación Firebase', [
                        'organizador_id' => $organizador->id,
                        'tiene_token' => !empty($organizador->fcm_token)
                    ]);
                }
            } else {
                Log::info('Organizador sin token FCM, notificación no enviada', [
                    'organizador_id' => $organizador->id,
                    'organizador_email' => $organizador->email
                ]);
            }

            Log::info('Job NotificarOrganizadorEvento completado', [
                'ficha_uuid' => $this->fichaUuid,
                'mensaje' => $mensaje,
                'detalle' => $detalle
            ]);
        } catch (\Exception $e) {
            Log::error('Error en Job NotificarOrganizadorEvento', [
                'ficha_uuid' => $this->fichaUuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Job NotificarOrganizadorEvento falló definitivamente', [
            'ficha_uuid' => $this->fichaUuid,
            'usuario_id' => $this->usuarioId,
            'accion' => $this->accion,
            'error' => $exception->getMessage()
        ]);
    }
}
