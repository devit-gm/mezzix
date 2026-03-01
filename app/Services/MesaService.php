<?php

namespace App\Services;

use App\Models\Ficha;
use App\Models\FichaProducto;
use App\Models\FichaServicio;
use App\Models\FichaRecibo;
use App\Models\MesaHistorial;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Servicio para gestionar la lógica de negocio de Mesas
 */
class MesaService
{
    /**
     * @var FichaService
     */
    protected $fichaService;
    
    public function __construct(FichaService $fichaService)
    {
        $this->fichaService = $fichaService;
    }
    
    /**
     * Calcular el importe total de una mesa
     * 
     * @param Ficha $mesa
     * @return float
     */
    public function calcularImporte(Ficha $mesa): float
    {
        return $this->fichaService->calcularConsumos($mesa);
    }
    
    /**
     * Obtener desglose de consumos de una mesa
     * 
     * @param Ficha $mesa
     * @return array
     */
    public function obtenerDesglose(Ficha $mesa): array
    {
        $productos = FichaProducto::where('id_ficha', $mesa->uuid)
            ->with('producto')
            ->get()
            ->groupBy('id_producto')
            ->map(function ($items) {
                return [
                    'producto' => $items->first()->producto,
                    'cantidad' => $items->sum('cantidad'),
                    'precio' => $items->sum('precio')
                ];
            });
        
        $servicios = FichaServicio::where('id_ficha', $mesa->uuid)
            ->with('servicio')
            ->get();
        
        return [
            'productos' => $productos,
            'servicios' => $servicios,
            'total_productos' => $productos->sum('precio'),
            'total_servicios' => $servicios->sum('precio'),
            'total' => $this->calcularImporte($mesa)
        ];
    }
    
    /**
     * Verificar si una mesa está libre
     * 
     * @param Ficha $mesa
     * @return bool
     */
    public function estaLibre(Ficha $mesa): bool
    {
        return $mesa->estado_mesa === 'libre';
    }
    
    /**
     * Verificar si una mesa está ocupada
     * 
     * @param Ficha $mesa
     * @return bool
     */
    public function estaOcupada(Ficha $mesa): bool
    {
        return $mesa->estado_mesa === 'ocupada';
    }
    
    /**
     * Verificar si un usuario es el camarero asignado
     * 
     * @param Ficha $mesa
     * @param int $userId
     * @return bool
     */
    public function esCamareroAsignado(Ficha $mesa, int $userId): bool
    {
        return $mesa->camarero_id == $userId;
    }
    
    /**
     * Abrir una mesa
     * 
     * @param Ficha $mesa
     * @param int $camareroId
     * @param int $numeroComensales
     * @param string|null $notas
     * @return void
     */
    public function abrir(Ficha $mesa, int $camareroId, int $numeroComensales, ?string $notas = null): void
    {
        $mesa->update([
            'estado_mesa' => 'ocupada',
            'camarero_id' => $camareroId,
            'numero_comensales' => $numeroComensales,
            'hora_apertura' => now(),
            'observaciones' => $notas ?? ''
        ]);
        
        $this->registrarHistorial($mesa, 'abrir', $camareroId, [
            'comensales' => $numeroComensales,
            'notas' => $notas
        ]);
    }
    
    /**
     * Transferir mesa a otro camarero
     * 
     * @param Ficha $mesa
     * @param int $nuevoCamareroId
     * @return void
     */
    public function transferir(Ficha $mesa, int $nuevoCamareroId): void
    {
        $camareroAnterior = $mesa->camarero_id;
        
        $mesa->update([
            'ultimo_camarero_id' => $camareroAnterior,
            'camarero_id' => $nuevoCamareroId
        ]);
        
        $this->registrarHistorial($mesa, 'tomar', $nuevoCamareroId, [
            'camarero_anterior_id' => $camareroAnterior,
            'importe_actual' => $this->calcularImporte($mesa)
        ]);
    }
    
    /**
     * Cerrar mesa y generar recibo
     * 
     * @param Ficha $mesa
     * @param string $metodoPago
     * @param float $propina
     * @return FichaRecibo
     */
    public function cerrar(Ficha $mesa, string $metodoPago, float $propina = 0): FichaRecibo
    {
        $importeTotal = $this->calcularImporte($mesa);
        $importeFinal = $importeTotal + $propina;
        
        // Crear recibo
        $recibo = FichaRecibo::create([
            'uuid' => (string) Str::uuid(),
            'id_ficha' => $mesa->uuid,
            'user_id' => $mesa->camarero_id,
            'tipo' => 1, // Tipo mesa
            'estado' => 1, // Pagado
            'precio' => $importeFinal,
            'metodo_pago' => $metodoPago,
            'propina' => $propina,
            'fecha' => now()
        ]);
        
        // Cerrar mesa
        $mesa->update([
            'estado_mesa' => 'cerrada',
            'hora_cierre' => now(),
            'precio' => $importeFinal,
            'metodo_pago' => $metodoPago
        ]);
        
        $this->registrarHistorial($mesa, 'cerrar', $mesa->camarero_id, [
            'importe_total' => $importeTotal,
            'propina' => $propina,
            'metodo_pago' => $metodoPago
        ]);
        
        return $recibo;
    }
    
    /**
     * Liberar mesa (dejarla libre sin cerrarla)
     * 
     * @param Ficha $mesa
     * @param int $userId
     * @return void
     */
    public function liberar(Ficha $mesa, int $userId): void
    {
        $mesa->update([
            'estado_mesa' => 'libre',
            'camarero_id' => null,
            'numero_comensales' => null,
            'hora_apertura' => null,
            'observaciones' => null
        ]);
        
        $this->registrarHistorial($mesa, 'liberar', $userId, [
            'importe_perdido' => $this->calcularImporte($mesa)
        ]);
    }
    
    /**
     * Verificar si hay productos pendientes en cocina
     * 
     * @param Ficha $mesa
     * @return bool
     */
    public function hayProductosPendientesEnCocina(Ficha $mesa): bool
    {
        return FichaProducto::where('id_ficha', $mesa->uuid)
            ->whereIn('estado', ['pendiente', 'en_preparacion'])
            ->exists();
    }
    
    /**
     * Enviar productos a cocina
     * 
     * @param Ficha $mesa
     * @return int Número de productos enviados
     */
    public function enviarACocina(Ficha $mesa): int
    {
        $productos = FichaProducto::with('producto.familiaObj')
            ->where('id_ficha', $mesa->uuid)
            ->whereNull('estado')
            ->get();
        
        $enviados = 0;
        
        foreach ($productos as $producto) {
            $productoModel = $producto->producto;
            $familia = $productoModel && $productoModel->familiaObj ? $productoModel->familiaObj : null;
            
            // Solo enviar si la familia debe mostrarse en cocina
            if ($familia && $familia->mostrar_en_cocina) {
                $producto->update([
                    'estado' => 'pendiente',
                    'enviado_cocina_at' => now()
                ]);
                $enviados++;
            }
        }
        
        if ($enviados > 0) {
            $this->registrarHistorial($mesa, 'enviar_cocina', $mesa->camarero_id, [
                'productos_enviados' => $enviados
            ]);
        }
        
        return $enviados;
    }
    
    /**
     * Registrar acción en historial de mesa
     * 
     * @param Ficha $mesa
     * @param string $accion
     * @param int $camareroId
     * @param array $detalles
     * @return void
     */
    protected function registrarHistorial(Ficha $mesa, string $accion, int $camareroId, array $detalles = []): void
    {
        MesaHistorial::create([
            'mesa_id' => $mesa->uuid,
            'accion' => $accion,
            'camarero_id' => $camareroId,
            'detalles' => json_encode($detalles),
            'created_at' => now()
        ]);
    }
    
    /**
     * Calcular tiempo de ocupación de una mesa
     * 
     * @param Ficha $mesa
     * @return int Minutos de ocupación
     */
    public function calcularTiempoOcupacion(Ficha $mesa): int
    {
        if (!$mesa->hora_apertura) {
            return 0;
        }
        
        $horaFin = $mesa->hora_cierre ?? now();
        
        return $mesa->hora_apertura->diffInMinutes($horaFin);
    }
}
