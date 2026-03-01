<?php

namespace App\Services;

use App\Models\Producto;
use App\Models\FichaProducto;
use Illuminate\Support\Facades\DB;

/**
 * Servicio para gestionar la lógica de negocio de Productos
 */
class ProductoService
{
    /**
     * Verificar si hay stock disponible
     * 
     * @param Producto $producto
     * @param int $cantidad
     * @return bool
     */
    public function tieneStockDisponible(Producto $producto, int $cantidad): bool
    {
        // Si es combinado, verificar componentes
        if ($producto->combinado == 1) {
            return $this->tieneStockDisponibleCombinado($producto, $cantidad);
        }
        
        return $producto->stock_disponible >= $cantidad;
    }
    
    /**
     * Verificar stock disponible de producto combinado
     * 
     * @param Producto $producto
     * @param int $cantidad
     * @return bool
     */
    protected function tieneStockDisponibleCombinado(Producto $producto, int $cantidad): bool
    {
        $producto->load('composicion.componenteProducto');
        
        foreach ($producto->composicion as $composicion) {
            $componente = $composicion->componenteProducto;
            
            if (!$componente) {
                continue;
            }
            
            $cantidadNecesaria = $cantidad * ($composicion->cantidad ?? 1);
            
            if ($componente->stock_disponible < $cantidadNecesaria) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Reservar stock de un producto
     * 
     * @param Producto $producto
     * @param int $cantidad
     * @return bool
     */
    public function reservarStock(Producto $producto, int $cantidad): bool
    {
        if (!$this->tieneStockDisponible($producto, $cantidad)) {
            return false;
        }
        
        if ($producto->combinado == 1) {
            return $this->reservarStockCombinado($producto, $cantidad);
        }
        
        $producto->reservarStock($cantidad);
        
        return true;
    }
    
    /**
     * Reservar stock de producto combinado
     * 
     * @param Producto $producto
     * @param int $cantidad
     * @return bool
     */
    protected function reservarStockCombinado(Producto $producto, int $cantidad): bool
    {
        $producto->load('composicion.componenteProducto');
        
        foreach ($producto->composicion as $composicion) {
            $componente = $composicion->componenteProducto;
            
            if (!$componente) {
                continue;
            }
            
            $cantidadReservar = $cantidad * ($composicion->cantidad ?? 1);
            $componente->reservarStock($cantidadReservar);
        }
        
        return true;
    }
    
    /**
     * Liberar stock reservado
     * 
     * @param Producto $producto
     * @param int $cantidad
     * @return void
     */
    public function liberarStock(Producto $producto, int $cantidad): void
    {
        if ($producto->combinado == 1) {
            $this->liberarStockCombinado($producto, $cantidad);
            return;
        }
        
        $producto->liberarStock($cantidad);
    }
    
    /**
     * Liberar stock de producto combinado
     * 
     * @param Producto $producto
     * @param int $cantidad
     * @return void
     */
    protected function liberarStockCombinado(Producto $producto, int $cantidad): void
    {
        $producto->load('composicion.componenteProducto');
        
        foreach ($producto->composicion as $composicion) {
            $componente = $composicion->componenteProducto;
            
            if (!$componente) {
                continue;
            }
            
            $cantidadLiberar = $cantidad * ($composicion->cantidad ?? 1);
            $componente->liberarStock($cantidadLiberar);
        }
    }
    
    /**
     * Confirmar stock reservado (convertir a vendido)
     * 
     * @param Producto $producto
     * @param int $cantidad
     * @return void
     */
    public function confirmarStock(Producto $producto, int $cantidad): void
    {
        if ($producto->combinado == 1) {
            $this->confirmarStockCombinado($producto, $cantidad);
            return;
        }
        
        // Liberar reserva y descontar del stock real
        $producto->liberarStock($cantidad);
        $producto->decrement('stock', $cantidad);
    }
    
    /**
     * Confirmar stock de producto combinado
     * 
     * @param Producto $producto
     * @param int $cantidad
     * @return void
     */
    protected function confirmarStockCombinado(Producto $producto, int $cantidad): void
    {
        $producto->load('composicion.componenteProducto');
        
        foreach ($producto->composicion as $composicion) {
            $componente = $composicion->componenteProducto;
            
            if (!$componente) {
                continue;
            }
            
            $cantidadConfirmar = $cantidad * ($composicion->cantidad ?? 1);
            $componente->liberarStock($cantidadConfirmar);
            $componente->decrement('stock', $cantidadConfirmar);
        }
    }
    
    /**
     * Calcular precio de venta con IVA
     * 
     * @param Producto $producto
     * @return float
     */
    public function calcularPrecioConIva(Producto $producto): float
    {
        $iva = $producto->iva ?? 21;
        return $producto->precio * (1 + $iva / 100);
    }
    
    /**
     * Calcular margen de beneficio
     * 
     * @param Producto $producto
     * @return array ['margen_porcentaje' => float, 'margen_euros' => float]
     */
    public function calcularMargen(Producto $producto): array
    {
        if (!$producto->precio_compra || $producto->precio_compra <= 0) {
            return [
                'margen_porcentaje' => 0,
                'margen_euros' => 0
            ];
        }
        
        $margenEuros = $producto->precio - $producto->precio_compra;
        $margenPorcentaje = ($margenEuros / $producto->precio_compra) * 100;
        
        return [
            'margen_porcentaje' => round($margenPorcentaje, 2),
            'margen_euros' => round($margenEuros, 2)
        ];
    }
    
    /**
     * Verificar si el stock está bajo el mínimo
     * 
     * @param Producto $producto
     * @return bool
     */
    public function estaStockBajo(Producto $producto): bool
    {
        if (!$producto->stock_minimo) {
            return false;
        }
        
        return $producto->stock <= $producto->stock_minimo;
    }
    
    /**
     * Obtener productos con stock bajo
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function obtenerProductosStockBajo()
    {
        return Producto::whereColumn('stock', '<=', 'stock_minimo')
            ->where('stock_minimo', '>', 0)
            ->orderBy('stock', 'asc')
            ->get();
    }
    
    /**
     * Obtener productos más vendidos
     * 
     * @param int $limite
     * @param string|null $fechaDesde
     * @return array
     */
    public function obtenerMasVendidos(int $limite = 10, ?string $fechaDesde = null)
    {
        $query = FichaProducto::select('id_producto', DB::raw('SUM(cantidad) as total_vendido'))
            ->groupBy('id_producto')
            ->orderByDesc('total_vendido')
            ->limit($limite);
        
        if ($fechaDesde) {
            $query->where('created_at', '>=', $fechaDesde);
        }
        
        return $query->with('producto')->get()->map(function ($item) {
            return [
                'producto' => $item->producto,
                'total_vendido' => $item->total_vendido
            ];
        });
    }
}
