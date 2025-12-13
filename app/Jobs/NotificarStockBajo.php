<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\StockNotificationService;
use Illuminate\Support\Facades\Log;

class NotificarStockBajo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * UUID del producto a verificar
     */
    public $productoUuid;

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
    public function __construct(string $productoUuid)
    {
        $this->productoUuid = $productoUuid;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Job NotificarStockBajo iniciado', [
            'producto_uuid' => $this->productoUuid
        ]);

        try {
            $stockService = new StockNotificationService();
            $stockService->verificarYNotificar($this->productoUuid);

            Log::info('Job NotificarStockBajo completado', [
                'producto_uuid' => $this->productoUuid
            ]);
        } catch (\Exception $e) {
            Log::error('Error en Job NotificarStockBajo', [
                'producto_uuid' => $this->productoUuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-lanzar excepción para que el job se reintente
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Job NotificarStockBajo falló definitivamente', [
            'producto_uuid' => $this->productoUuid,
            'error' => $exception->getMessage()
        ]);
    }
}
