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

    /**
     * Atributos calculados que se añaden al JSON
     */
    protected $appends = ['stock_disponible'];

    /*
     |--------------------------------------------------------------------------
     | ATRIBUTOS CALCULADOS
     |--------------------------------------------------------------------------
     */

    /**
     * Atributo calculado: stock disponible (stock real - stock reservado)
     */
    public function getStockDisponibleAttribute()
    {
        return max(0, ($this->stock ?? 0) - ($this->stock_reservado ?? 0));
    }

    /*
     |--------------------------------------------------------------------------
     | MÉTODOS DE CÁLCULO
     |--------------------------------------------------------------------------
     */

    /**
     * Obtener stock disponible (stock real - stock reservado)
     * @deprecated Usar $producto->stock_disponible en su lugar
     */
    public function stockDisponible()
    {
        return $this->stock_disponible;
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
     * Optimizado: 1 query en lugar de 2
     */
    public function confirmarVenta($cantidad)
    {
        if ($this->stock === null) {
            return true; // No gestiona stock
        }

        // Batch update: descuenta stock real y libera reserva en una sola query
        $this->update([
            'stock' => \DB::raw('stock - ' . (int)$cantidad),
            'stock_reservado' => \DB::raw('GREATEST(0, stock_reservado - ' . (int)$cantidad . ')')
        ]);
        
        // Refrescar el modelo para reflejar los cambios
        $this->refresh();
        
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
        return $this->hasMany(ComposicionProducto::class, 'id_producto', 'uuid')
            ->with('componenteProducto:uuid,nombre,precio,stock,stock_reservado,combinado,iva'); // Eager load para evitar N+1
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
