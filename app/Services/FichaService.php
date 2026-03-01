<?php

namespace App\Services;

use App\Models\Ficha;
use App\Models\FichaProducto;
use App\Models\FichaServicio;
use App\Models\FichaGasto;
use App\Models\FichaUsuario;
use App\Models\Ajustes;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para gestionar la lógica de negocio de Fichas
 */
class FichaService
{
    /**
     * Calcular el importe total de una ficha
     * 
     * @param Ficha $ficha
     * @param bool $sumarInvitados
     * @return float
     */
    public function calcularImporte(Ficha $ficha, bool $sumarInvitados = false): float
    {
        // Usar ajustes cacheados si están disponibles
        $ajustes = app()->has('ajustes') ? get_ajustes() : Ajustes::first();
        
        if (!$ajustes) {
            Log::warning('Ajustes no encontrado en calcularImporte', ['ficha_uuid' => $ficha->uuid]);
            $ajustes = new Ajustes();
        }
        
        // En modo agencia de eventos, el precio viene del campo precio
        if ($ajustes->modo_operacion === 'agencia_eventos' && $ficha->tipo == 4) {
            return $ficha->precio ?? 0;
        }
        
        // Sumar consumos, servicios y gastos
        $precio = $this->calcularConsumos($ficha);
        
        // Añadir invitados si corresponde
        if ($sumarInvitados) {
            $precio += $this->calcularInvitados($ficha, $ajustes);
        }
        
        // Añadir invitados de grupo
        if (($ajustes->activar_invitados_grupo ?? false) && $ficha->invitados_grupo > 0) {
            $precio += $ficha->invitados_grupo;
        }
        
        return $precio;
    }
    
    /**
     * Calcular solo los consumos (productos + servicios + gastos)
     * 
     * @param Ficha $ficha
     * @return float
     */
    public function calcularConsumos(Ficha $ficha): float
    {
        $precio = FichaProducto::where('id_ficha', $ficha->uuid)->sum('precio');
        $precio += FichaServicio::where('id_ficha', $ficha->uuid)->sum('precio');
        $precio += FichaGasto::where('id_ficha', $ficha->uuid)->sum('precio');
        
        return $precio;
    }
    
    /**
     * Calcular coste de invitados
     * 
     * @param Ficha $ficha
     * @param Ajustes $ajustes
     * @return float
     */
    public function calcularInvitados(Ficha $ficha, Ajustes $ajustes): float
    {
        if (!$ajustes->uuid) {
            return 0;
        }
        
        $usuarios = FichaUsuario::where('id_ficha', $ficha->uuid)->get(['invitados']);
        $precioTotal = 0;
        
        foreach ($usuarios as $usuario) {
            $num_invitados = $usuario->invitados;
            
            // Aplicar límite máximo de invitados a cobrar
            if ($num_invitados > ($ajustes->max_invitados_cobrar ?? 0)) {
                $num_invitados = $ajustes->max_invitados_cobrar ?? 0;
            }
            
            // Aplicar "primer invitado gratis"
            if (($ajustes->primer_invitado_gratis ?? false) && $num_invitados > 0) {
                $num_invitados--;
            }
            
            $precioTotal += $num_invitados * ($ajustes->precio_invitado ?? 0);
        }
        
        return $precioTotal;
    }
    
    /**
     * Obtener desglose de precios de una ficha
     * 
     * @param Ficha $ficha
     * @return array
     */
    public function obtenerDesglose(Ficha $ficha): array
    {
        return [
            'productos' => FichaProducto::where('id_ficha', $ficha->uuid)->sum('precio'),
            'servicios' => FichaServicio::where('id_ficha', $ficha->uuid)->sum('precio'),
            'gastos' => FichaGasto::where('id_ficha', $ficha->uuid)->sum('precio'),
            'invitados' => $this->calcularInvitados($ficha, get_ajustes() ?? Ajustes::first()),
            'total' => $this->calcularImporte($ficha, true)
        ];
    }
    
    /**
     * Verificar si el usuario puede ver la ficha
     * 
     * @param Ficha $ficha
     * @param int $userId
     * @return bool
     */
    public function puedeVerFicha(Ficha $ficha, int $userId): bool
    {
        // Es el propietario
        if ($ficha->user_id == $userId) {
            return true;
        }
        
        // Está inscrito en la ficha
        $inscrito = FichaUsuario::where('id_ficha', $ficha->uuid)
            ->where('user_id', $userId)
            ->exists();
        
        return $inscrito;
    }
    
    /**
     * Verificar si una ficha está disponible para inscripción
     * 
     * @param Ficha $ficha
     * @return array ['disponible' => bool, 'razon' => string|null]
     */
    public function verificarDisponibilidadInscripcion(Ficha $ficha): array
    {
        // Solo eventos permiten inscripción
        if ($ficha->tipo != 4) {
            return ['disponible' => false, 'razon' => 'Solo los eventos permiten inscripción'];
        }
        
        // Verificar aforo
        if ($ficha->aforo_maximo && $ficha->inscritos_actuales >= $ficha->aforo_maximo) {
            return ['disponible' => false, 'razon' => 'Aforo completo'];
        }
        
        // Verificar fecha
        if ($ficha->fecha && $ficha->fecha < now()) {
            return ['disponible' => false, 'razon' => 'El evento ya ha pasado'];
        }
        
        return ['disponible' => true, 'razon' => null];
    }
    
    /**
     * Inscribir usuario en un evento
     * 
     * @param Ficha $ficha
     * @param int $userId
     * @return bool
     */
    public function inscribirUsuario(Ficha $ficha, int $userId): bool
    {
        // Verificar disponibilidad
        $disponibilidad = $this->verificarDisponibilidadInscripcion($ficha);
        if (!$disponibilidad['disponible']) {
            return false;
        }
        
        // Verificar que no esté ya inscrito
        $yaInscrito = FichaUsuario::where('id_ficha', $ficha->uuid)
            ->where('user_id', $userId)
            ->exists();
        
        if ($yaInscrito) {
            return false;
        }
        
        // Inscribir
        FichaUsuario::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'id_ficha' => $ficha->uuid,
            'user_id' => $userId,
            'invitados' => 0,
            'ninos' => 0
        ]);
        
        // Incrementar contador
        $ficha->increment('inscritos_actuales');
        
        return true;
    }
    
    /**
     * Cancelar inscripción de usuario
     * 
     * @param Ficha $ficha
     * @param int $userId
     * @return bool
     */
    public function cancelarInscripcion(Ficha $ficha, int $userId): bool
    {
        $inscripcion = FichaUsuario::where('id_ficha', $ficha->uuid)
            ->where('user_id', $userId)
            ->first();
        
        if (!$inscripcion) {
            return false;
        }
        
        $inscripcion->delete();
        $ficha->decrement('inscritos_actuales');
        
        return true;
    }
}
