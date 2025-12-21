<?php

namespace App\Http\Controllers;

use App\Models\Ficha;
use App\Models\FichaUsuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EventosPublicosController extends Controller
{
    /**
     * Mostrar catálogo público de eventos disponibles
     */
    public function index()
    {
        $ajustes = app('ajustes');
        
        // Solo disponible en modo agencia_eventos
        if ($ajustes->modo_operacion !== 'agencia_eventos') {
            return redirect()->route('home');
        }

        // Obtener eventos futuros o actuales, ordenados por fecha
        $eventos = Ficha::where('tipo', 4) // tipo 4 para eventos de agencia
            ->where('estado', 0) // estado 0 = abierto para inscripciones
            ->where('fecha', '>=', now()->toDateString())
            ->orderBy('fecha', 'asc')
            ->with('inscritos')
            ->get();

        return view('eventos-publicos.index', compact('eventos'));
    }

    /**
     * Mostrar detalle de un evento
     */
    public function show($uuid)
    {
        $ajustes = app('ajustes');
        
        if ($ajustes->modo_operacion !== 'agencia_eventos') {
            return redirect()->route('home');
        }

        $evento = Ficha::with(['inscritos.user', 'usuario'])
            ->findOrFail($uuid);

        // Verificar si el usuario actual está inscrito
        $estaInscrito = false;
        if (Auth::check()) {
            $estaInscrito = DB::connection('site')
                ->table('fichas_usuarios')
                ->where('id_ficha', $uuid)
                ->where('user_id', Auth::id())
                ->exists();
        }

        // Calcular plazas disponibles
        $plazasDisponibles = null;
        if ($evento->aforo_maximo) {
            $plazasDisponibles = $evento->aforo_maximo - $evento->inscritos_actuales;
        }

        return view('eventos-publicos.show', compact('evento', 'estaInscrito', 'plazasDisponibles'));
    }

    /**
     * Inscribir al usuario autenticado en un evento
     */
    public function inscribirse(Request $request, $uuid)
    {
        \Log::info('=== INICIO inscribirse ===', ['uuid' => $uuid, 'user_id' => Auth::id()]);
        
        if (!Auth::check()) {
            \Log::warning('Usuario no autenticado intentando inscribirse');
            return redirect()->route('login')->with('error', __('Debes iniciar sesión para inscribirte'));
        }

        $evento = Ficha::findOrFail($uuid);
        \Log::info('Evento encontrado', ['evento_id' => $evento->uuid, 'descripcion' => $evento->descripcion]);
        
        $ajustes = app('ajustes');
        
        if ($ajustes->modo_operacion !== 'agencia_eventos') {
            \Log::warning('Intento de inscripción fuera de modo agencia');
            return redirect()->route('home');
        }

        // Verificar que el evento esté abierto
        if ($evento->estado != 0) {
            \Log::warning('Evento no disponible', ['estado' => $evento->estado]);
            return redirect()->back()->with('error', __('Este evento ya no está disponible para inscripciones'));
        }

        // Verificar que no haya pasado la fecha
        if ($evento->fecha < now()->toDateString()) {
            \Log::warning('Evento ya pasado', ['fecha' => $evento->fecha]);
            return redirect()->back()->with('error', __('Este evento ya ha pasado'));
        }

        // Verificar si ya está inscrito
        $yaInscrito = DB::connection('site')
            ->table('fichas_usuarios')
            ->where('id_ficha', $uuid)
            ->where('user_id', Auth::id())
            ->exists();

        \Log::info('Verificación inscripción previa', ['ya_inscrito' => $yaInscrito]);

        if ($yaInscrito) {
            return redirect()->back()->with('info', __('Ya estás inscrito en este evento'));
        }

        // Verificar aforo disponible
        if ($evento->aforo_maximo && $evento->inscritos_actuales >= $evento->aforo_maximo) {
            \Log::warning('Aforo completo', ['aforo_maximo' => $evento->aforo_maximo, 'inscritos' => $evento->inscritos_actuales]);
            return redirect()->back()->with('error', __('No hay plazas disponibles'));
        }

        DB::connection('site')->beginTransaction();
        
        try {
            \Log::info('Iniciando inserción en fichas_usuarios');
            
            // Crear inscripción usando la conexión site
            DB::connection('site')->table('fichas_usuarios')->insert([
                'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                'id_ficha' => $uuid,
                'user_id' => Auth::id(),
                'invitados' => 0,
                'ninos' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            \Log::info('Inscripción creada, incrementando contador');

            // Incrementar contador de inscritos
            $evento->increment('inscritos_actuales');

            DB::connection('site')->commit();
            
            \Log::info('Inscripción completada exitosamente');

            // Enviar notificaciones
            $this->enviarNotificacionesInscripcion($evento, Auth::user(), 'inscrito');

            return redirect()->route('eventos-publicos.show', $uuid)
                ->with('success', __('Te has inscrito correctamente al evento'));

        } catch (\Exception $e) {
            DB::connection('site')->rollBack();
            \Log::error('Error al procesar inscripción', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', __('Error al procesar la inscripción'));
        }
    }

    /**
     * Cancelar inscripción del usuario
     */
    public function cancelarInscripcion($uuid)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $evento = Ficha::findOrFail($uuid);
        $ajustes = app('ajustes');
        
        if ($ajustes->modo_operacion !== 'agencia_eventos') {
            return redirect()->route('home');
        }

        DB::connection('site')->beginTransaction();
        
        try {
            // Eliminar inscripción usando la conexión site
            $eliminados = DB::connection('site')
                ->table('fichas_usuarios')
                ->where('id_ficha', $uuid)
                ->where('user_id', Auth::id())
                ->delete();

            if ($eliminados > 0) {
                // Decrementar contador
                $evento->decrement('inscritos_actuales');
                
                // Enviar notificaciones
                $this->enviarNotificacionesInscripcion($evento, Auth::user(), 'cancelado');
            }

            DB::connection('site')->commit();

            return redirect()->route('eventos-publicos.show', $uuid)
                ->with('success', __('Has cancelado correctamente la inscripción al evento'));


        } catch (\Exception $e) {
            DB::connection('site')->rollBack();
            return redirect()->back()->with('error', __('Error al cancelar la inscripción'));
        }
    }

    /**
     * Ver mis inscripciones
     */
    public function misInscripciones()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $ajustes = app('ajustes');
        
        if ($ajustes->modo_operacion !== 'agencia_eventos') {
            return redirect()->route('home');
        }

        // Obtener eventos donde el usuario está inscrito
        $misEventos = Ficha::whereHas('inscritos', function($query) {
                $query->where('user_id', Auth::id());
            })
            ->with('inscritos')
            ->orderBy('fecha', 'desc')
            ->get();

        return view('eventos-publicos.mis-inscripciones', compact('misEventos'));
    }

    /**
     * Enviar notificaciones de inscripción/cancelación
     */
    private function enviarNotificacionesInscripcion($evento, $usuario, $accion)
    {
        try {
            $firebase = app(\App\Services\FirebaseService::class);
            
            // Notificación al usuario que se inscribió/canceló
            if ($usuario->fcm_token) {
                $tituloUsuario = $accion === 'inscrito' 
                    ? __('Inscripción confirmada') 
                    : __('Inscripción cancelada');
                $mensajeUsuario = $accion === 'inscrito'
                    ? __('Te has inscrito correctamente en: :evento', ['evento' => $evento->descripcion])
                    : __('Has cancelado tu inscripción en: :evento', ['evento' => $evento->descripcion]);
                
                $firebase->sendNotification(
                    $usuario->fcm_token,
                    $tituloUsuario,
                    $mensajeUsuario,
                    ['tipo' => 'evento', 'evento_id' => $evento->uuid]
                );
            }

            // Notificación al creador del evento
            $creador = \App\Models\User::where('site_id', app('site')->id)
                ->find($evento->user_id);
                
            if ($creador && $creador->fcm_token && $creador->id !== $usuario->id) {
                $ocupacion = $evento->inscritos_actuales . '/' . $evento->aforo_maximo;
                
                $tituloCreador = $accion === 'inscrito'
                    ? __('Nueva inscripción')
                    : __('Inscripción cancelada');
                $mensajeCreador = $accion === 'inscrito'
                    ? __(':usuario se ha inscrito en tu evento: :evento (:ocupacion asistentes)', [
                        'usuario' => $usuario->name, 
                        'evento' => $evento->descripcion,
                        'ocupacion' => $ocupacion
                    ])
                    : __(':usuario ha cancelado su inscripción en tu evento: :evento (:ocupacion asistentes)', [
                        'usuario' => $usuario->name, 
                        'evento' => $evento->descripcion,
                        'ocupacion' => $ocupacion
                    ]);
                
                $firebase->sendNotification(
                    $creador->fcm_token,
                    $tituloCreador,
                    $mensajeCreador,
                    ['tipo' => 'evento', 'evento_id' => $evento->uuid]
                );
            }
            
        } catch (\Exception $e) {
            \Log::error('Error en enviarNotificacionesInscripcion', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
