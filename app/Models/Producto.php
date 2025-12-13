<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory;

    protected $connection = 'site';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'nombre',
        'imagen',
        'posicion',
        'familia',
        'combinado',
        'precio',
        'ean13',
        'iva',
        'stock',
        'stock_reservado'
    ];

    /*
     |--------------------------------------------------------------------------
     | MÉTODOS DE CÁLCULO
     |--------------------------------------------------------------------------
     */

    /**
     * Obtener stock disponible (stock real - stock reservado)
     */
    public function stockDisponible()
    {
        return ($this->stock ?? 0) - ($this->stock_reservado ?? 0);
    }

    /**
     * Verificar si hay stock disponible suficiente
     */
    public function tieneStockDisponible($cantidadSolicitada)
    {
        // Si no tiene stock configurado, consideramos que hay disponibilidad ilimitada
        if ($this->stock === null) {
            return true;
        }
        
        return $this->stockDisponible() >= $cantidadSolicitada;
    }

    /**
     * Reservar stock para una ficha
     */
    public function reservarStock($cantidad)
    {
        if ($this->stock === null) {
            return true; // No gestiona stock
        }

        $this->increment('stock_reservado', $cantidad);
        return true;
    }

    /**
     * Liberar stock reservado
     */
    public function liberarStock($cantidad)
    {
        if ($this->stock === null) {
            return true; // No gestiona stock
        }

        $nuevoStockReservado = max(0, ($this->stock_reservado ?? 0) - $cantidad);
        $this->update(['stock_reservado' => $nuevoStockReservado]);
        return true;
    }

    /**
     * Confirmar venta (descontar de stock real y liberar reserva)
     */
    public function confirmarVenta($cantidad)
    {
        if ($this->stock === null) {
            return true; // No gestiona stock
        }

        // Descontar del stock real
        $this->decrement('stock', $cantidad);
        
        // Liberar la reserva
        $this->liberarStock($cantidad);
        
        return true;
    }

    /**
     * Calcular precio con IVA incluido (el precio ya viene con IVA)
     */
    public function precioConIva()
    {
        return $this->precio; // El precio ya incluye IVA
    }

    /**
     * Calcular solo el importe del IVA
     */
    public function importeIva($cantidad = 1)
    {
        $iva = $this->iva ?? 0;
        $pvp = $this->precio * $cantidad;
        $baseImponible = $pvp / (1 + $iva / 100);
        return $pvp - $baseImponible;
    }

    /**
     * Obtener base imponible (precio sin IVA)
     */
    public function baseImponible($cantidad = 1)
    {
        $iva = $this->iva ?? 0;
        $pvp = $this->precio * $cantidad;
        return $pvp / (1 + $iva / 100);
    }

    /*
     |--------------------------------------------------------------------------
     | RELACIONES
     |--------------------------------------------------------------------------
     */

    // 🔹 Un producto pertenece a una familia
    public function familiaObj()
    {
        return $this->belongsTo(Familia::class, 'familia', 'uuid');
    }

    // 🔹 Todas las líneas de composición donde este producto es el principal
    public function composicion()
    {
        return $this->hasMany(ComposicionProducto::class, 'id_producto', 'uuid');
    }

    // 🔹 Productos componentes (productos hijos del combinado)
    public function componentes()
    {
        return $this->belongsToMany(
            Producto::class,
            'composicion_productos',
            'id_producto',      // FK hacia este producto
            'id_componente',    // FK hacia el componente
            'uuid',             // PK de este producto
            'uuid'              // PK del componente
        );
    }

    // 🔹 Las fichas donde aparece este producto
    public function fichas()
    {
        return $this->hasMany(FichaProducto::class, 'id_producto', 'uuid');
    }
}
