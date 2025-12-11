<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Proveedor extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'site';
    protected $table = 'proveedores';

    protected $fillable = [
        'uuid',
        'nombre',
        'cif',
        'email',
        'telefono',
        'direccion',
        'ciudad',
        'codigo_postal',
        'pais',
        'contacto_principal',
        'condiciones_pago',
        'dias_pago',
        'cuenta_bancaria',
        'notas',
        'activo',
        'descuento_general'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'dias_pago' => 'integer',
        'descuento_general' => 'decimal:2'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($proveedor) {
            if (empty($proveedor->uuid)) {
                $proveedor->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Relación con albaranes
     */
    public function albaranes()
    {
        return $this->hasMany(Albaran::class, 'proveedor_id');
    }

    /**
     * Obtener el total de compras a este proveedor
     */
    public function getTotalComprasAttribute()
    {
        return $this->albaranes()
            ->where('estado', 'recibido')
            ->sum('total');
    }

    /**
     * Obtener el número de albaranes
     */
    public function getNumeroAlbaranesAttribute()
    {
        return $this->albaranes()->count();
    }

    /**
     * Obtener albaranes pendientes
     */
    public function albaranesPendientes()
    {
        return $this->albaranes()->where('estado', 'pendiente');
    }

    /**
     * Obtener último albaran
     */
    public function ultimoAlbaran()
    {
        return $this->albaranes()->latest('created_at')->first();
    }

    /**
     * Scope para proveedores activos
     */
    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para búsqueda
     */
    public function scopeBuscar($query, $termino)
    {
        return $query->where(function ($q) use ($termino) {
            $q->where('nombre', 'like', "%{$termino}%")
              ->orWhere('cif', 'like', "%{$termino}%")
              ->orWhere('email', 'like', "%{$termino}%")
              ->orWhere('telefono', 'like', "%{$termino}%");
        });
    }
}
