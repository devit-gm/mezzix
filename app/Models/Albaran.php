<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Albaran extends Model
{
    use HasFactory;

    protected $connection = 'site';
    protected $table = 'albaranes';

    protected $fillable = [
        'proveedor_id',
        'numero_albaran',
        'fecha_albaran',
        'fecha',
        'estado',
        'total',
        'observaciones',
        'usuario_id',
        'fecha_recepcion',
    ];

    protected $casts = [
        'fecha' => 'date',
        'fecha_albaran' => 'date',
        'fecha_recepcion' => 'datetime',
        'total' => 'decimal:2',
    ];

    /**
     * Relación con las líneas del albarán
     */
    public function lineas()
    {
        return $this->hasMany(AlbaranLinea::class);
    }

    /**
     * Relación con el usuario que creó el albarán
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Relación con el proveedor
     */
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    /**
     * Calcular el total del albarán sumando todas las líneas
     */
    public function calcularTotal()
    {
        $this->total = $this->lineas()->sum('subtotal');
        $this->save();
        return $this->total;
    }

    /**
     * Confirmar la recepción del albarán y actualizar el stock
     */
    public function confirmarRecepcion()
    {
        if ($this->estado === 'recibido') {
            return false; // Ya está recibido
        }

        \DB::beginTransaction();
        try {
            // Actualizar stock de cada producto
            foreach ($this->lineas as $linea) {
                $producto = $linea->producto;
                if ($producto) {
                    $producto->stock += $linea->cantidad;
                    $producto->save();
                }
            }

            // Actualizar estado del albarán
            $this->estado = 'recibido';
            $this->fecha_recepcion = now();
            $this->save();

            \DB::commit();
            return true;
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    /**
     * Scope para filtrar por estado
     */
    public function scopeEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Scope para filtrar por proveedor
     */
    public function scopeProveedorId($query, $proveedorId)
    {
        return $query->where('proveedor_id', $proveedorId);
    }
}
