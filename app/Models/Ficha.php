<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid; // Add this line

class Ficha extends Model
{
    use HasFactory;
    protected $connection = 'site';
    protected $primaryKey = 'uuid';
    public $incrementing = false;

    protected $fillable = [
        'uuid',
        'user_id',
        'tipo',
        'estado',
        'descripcion',
        'invitados_grupo',
        'fecha',
        'precio',
        'hora',
        'menu',
        'responsables',
        'numero_mesa',
        'numero_comensales',
        'modo',
        'estado_mesa',
        'camarero_id',
        'hora_apertura',
        'hora_cierre',
        'ultimo_camarero_id',
        'importe',
        'nombre',
        'observaciones',
        'descripcion_evento',
        'foto_evento',
        'ubicacion_evento',
        'aforo_maximo',
        'inscritos_actuales',
        'es_infantil'
    ];

    protected $casts = [
        'es_infantil' => 'boolean',
        'fecha' => 'date',
        'hora_apertura' => 'datetime',
        'hora_cierre' => 'datetime'
    ];

    /**
     * Get the user that owns the Ficha.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function primerProducto()
    {
        return $this->productos()->orderBy('created_at');
    }
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function inscritos()
    {
        return $this->hasMany(FichaUsuario::class, 'id_ficha', 'uuid');
    }

    public function usuarios()
    {
        return $this->hasMany(FichaUsuario::class, 'id_ficha', 'uuid');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->{$model->getKeyName()} = Uuid::uuid4()->toString();
        });
    }

    public function productos() { return $this->hasMany(FichaProducto::class, 'id_ficha', 'uuid'); }
public function servicios() { return $this->hasMany(FichaServicio::class, 'id_ficha', 'uuid'); }
public function gastos() { return $this->hasMany(FichaGasto::class, 'id_ficha', 'uuid'); }

// Relaciones para mesas
public function camarero()
{
    return $this->belongsTo(User::class, 'camarero_id');
}

public function ultimoCamarero()
{
    return $this->belongsTo(User::class, 'ultimo_camarero_id');
}

public function historial()
{
    return $this->hasMany(MesaHistorial::class, 'mesa_id', 'uuid');
}

// Scopes útiles
public function scopeMesas($query)
{
    return $query->where('modo', 'mesa');
}

public function scopeFichas($query)
{
    return $query->where('modo', 'ficha');
}

public function scopeLibres($query)
{
    return $query->where('estado_mesa', 'libre');
}

public function scopeOcupadas($query)
{
    return $query->where('estado_mesa', 'ocupada');
}

public function scopeDeCamarero($query, $camareroId)
{
    return $query->where('camarero_id', $camareroId);
}

}
