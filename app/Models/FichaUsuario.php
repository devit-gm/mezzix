<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FichaUsuario extends Model
{
    use HasFactory;

    protected $table = 'fichas_usuarios';
    protected $connection = 'site';
    protected $primaryKey = 'uuid';
    public $incrementing = true;

    protected $fillable = [
        'uuid',
        'id_ficha',
        'user_id',
        'invitados',
        'ninos'
    ];

    /**
     * Get the ficha that owns the FichaUsuario.
     */
    public function ficha()
    {
        return $this->belongsTo(Ficha::class, 'id_ficha');
    }

    /**
     * Get the user that owns the FichaUsuario.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Alias para compatibilidad
     */
    public function usuario()
    {
        return $this->user();
    }
    
}
