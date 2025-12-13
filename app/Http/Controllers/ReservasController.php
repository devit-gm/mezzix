<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Reserva;
use App\Models\Site;
use App\Models\User; // Add this line to import the User model
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon; // Add this line to import the Carbon class
use Ramsey\Uuid\Uuid;
use App\Services\FirebaseService;

class ReservasController extends Controller
{
    public function index()
    {
        Carbon::setLocale(app()->getLocale());
        $ahora = Carbon::now();

        // Obtiene solo las reservas futuras o activas
        $reservas = Reserva::with('usuario') // evita N+1 en User::find()
            ->where(function ($q) use ($ahora) {
                $q->where('start_time', '>', $ahora)
                  ->orWhere('end_time', '>', $ahora)
                  ->orWhere(function ($q2) use ($ahora) {
                      $q2->where('start_time', '<', $ahora)
                         ->where('end_time', '>', $ahora);
                  });
            })
            ->orderBy('start_time')
            ->get();

        // Si no hay reservas, devolvemos directamente la vista
        if ($reservas->isEmpty()) {
            return view('reservas.index', [
                'reservas' => $reservas,
                'errors' => tap(new \Illuminate\Support\MessageBag(), function ($e) {
                    $e->add('msg', __('No se encontraron reservas para mostrar.'));
                })
            ]);
        }

        foreach ($reservas as $reserva) {
            // Convertimos solo una vez
            $start = Carbon::parse($reserva->start_time);
            $end = Carbon::parse($reserva->end_time);

            $reserva->start_time = $start->format('d/m/Y H:i');
            $reserva->end_time = $end->format('d/m/Y H:i');

            // Mes abreviado traducido
            $reserva->mes = mb_substr($start->translatedFormat('F'), 0, 3);
            $reserva->dia = $start->format('j');
            $reserva->hora = $start->format('H:i');

            // Borrable
            $reserva->borrable = ($reserva->user_id == Auth::id() || (Auth::check() && Auth::user()->role_id == 1));
        }

        return view('reservas.index', compact('reservas'));

    }

    public function create()
    {
        $userId = Auth::id();
        return view('reservas.create', compact('userId'));
    }

    public function store(Request $request, FirebaseService $firebase)
    {
        $request->validate([
            'name' => 'required|max:255',
            'user_id' => 'required',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
        ]);

        $messages = [
            'name.required' => 'El título es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico debe ser una dirección válida.',
            'start_time.required' => 'La hora de inicio es obligatoria.',
            'start_time.date' => 'La hora de inicio debe ser una fecha válida.',
            'end_time.required' => 'La hora de finalización es obligatoria.',
            'end_time.date' => 'La hora de finalización debe ser una fecha válida.',
            'end_time.after' => 'La hora de finalización debe ser posterior a la hora de inicio.',
        ];

        // Validar solapamiento de reservas
        $overlappingReservations = Reserva::where(function ($query) use ($request) {
            $query->whereBetween('start_time', [$request->start_time, $request->end_time])
                ->orWhereBetween('end_time', [$request->start_time, $request->end_time])
                ->orWhere(function ($query) use ($request) {
                    $query->where('start_time', '<', $request->start_time)
                        ->where('end_time', '>', $request->end_time);
                });
        })->exists();

        if ($overlappingReservations) {
            return back()->withErrors(['error' => __('Ya hay una reserva en esas fechas')])->withInput();
        }

        $reserva = Reserva::create([
            'uuid' => (string) Uuid::uuid4(),
            'name' => $request->name,
            'user_id' => $request->user_id,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time
        ]);

        // Enviar notificación a todos los usuarios del sitio activo con token FCM
        $siteId = app('site')->id;
        $usuarios = User::where('site_id', $siteId)
            ->whereNotNull('fcm_token')
            ->get();

        // Añadir superadmin (role_id = 1) si tiene token FCM y no está ya en la lista
        $superadmin = User::where('role_id', 1)
            ->whereNotNull('fcm_token')
            ->first();
        
        if ($superadmin && !$usuarios->contains(function($user) use ($superadmin) {
            return $user->id === $superadmin->id;
        })) {
            $usuarios->push($superadmin);
        }

        $fecha = Carbon::parse($request->start_time)->locale(app()->getLocale());
        $fechaFormateada = $fecha->isoFormat('D [de] MMMM [a las] HH:mm');
        
        // Usar array para evitar enviar a la misma persona dos veces
        $tokensEnviados = [];
        
        foreach ($usuarios as $usuario) {
            // Evitar duplicados por token
            if (in_array($usuario->fcm_token, $tokensEnviados)) {
                continue;
            }
            
            try {
                $firebase->sendNotification(
                    $usuario->fcm_token,
                    'Reservas',
                    '🛎️ Se ha realizado una nueva reserva',
                    [
                        'type' => 'reserva',
                        'reserva_id' => $reserva->id,
                        'url' => route('reservas.index')
                    ]
                );
                
                // Marcar token como enviado
                $tokensEnviados[] = $usuario->fcm_token;
            } catch (\Exception $e) {
                // Log error pero no interrumpir el flujo
                \Log::warning('Error al enviar notificación FCM: ' . $e->getMessage());
            }
        }

        return redirect()->route('reservas.index')->with('success', __('Reserva creada con éxito.'));
    }

    public function destroy(string $id)
    {
        $reserva = Reserva::find($id);
        $reserva->delete();
        return redirect()->route('reservas.index')
            ->with('success', __('Reserva eliminada con éxito'));
    }

    public function edit($id)
    {
        $userId = Auth::id();
        $reserva = Reserva::find($id);
        $reserva->usuario = User::find($reserva->user_id);
        return view('reservas.edit', compact('reserva', 'userId'));
    }

    public function update(Request $request, string $uuid)
    {
        $request->validate([
            'name' => 'required|max:255',
            'user_id' => 'required',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
        ]);

        $messages = [
            'name.required' => 'El título es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico debe ser una dirección válida.',
            'start_time.required' => 'La hora de inicio es obligatoria.',
            'start_time.date' => 'La hora de inicio debe ser una fecha válida.',
            'end_time.required' => 'La hora de finalización es obligatoria.',
            'end_time.date' => 'La hora de finalización debe ser una fecha válida.',
            'end_time.after' => 'La hora de finalización debe ser posterior a la hora de inicio.',
        ];

        // Validar solapamiento de reservas
        $overlappingReservations = Reserva::where(function ($query) use ($request) {
            $query->whereBetween('start_time', [$request->start_time, $request->end_time])
                ->orWhereBetween('end_time', [$request->start_time, $request->end_time])
                ->orWhere(function ($query) use ($request) {
                    $query->where('start_time', '<', $request->start_time)
                        ->where('end_time', '>', $request->end_time);
                });
        })->where('uuid', '<>', $request->uuid)->exists();

        if ($overlappingReservations) {
            return back()->withErrors(['error' => __('Ya hay una reserva en esas fechas')])->withInput();
        }
        $reserva = Reserva::where('uuid', $uuid)->first();
        $reserva->update([
            'name' => $request->name,
            'user_id' => $request->user_id,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time
        ]);

        return redirect()->route('reservas.index')->with('success', __('Reserva actualizada con éxito.'));
    }

    public function calendario(Request $request)
    {
        Carbon::setLocale(app()->getLocale());
        
        // Obtener mes y año actuales o los proporcionados
        $mes = $request->get('mes', now()->month);
        $año = $request->get('año', now()->year);
        
        // Crear fecha del primer día del mes
        $primerDia = Carbon::create($año, $mes, 1);
        $ultimoDia = $primerDia->copy()->endOfMonth();
        
        // Calcular mes anterior y siguiente
        $mesPrev = $primerDia->copy()->subMonth()->month;
        $añoPrev = $primerDia->copy()->subMonth()->year;
        $mesNext = $primerDia->copy()->addMonth()->month;
        $añoNext = $primerDia->copy()->addMonth()->year;
        
        // Nombre del mes en español
        $mesesNombres = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        $mesNombre = $mesesNombres[$mes];
        
        // Obtener todas las reservas del mes
        $reservas = Reserva::with('usuario')
            ->whereBetween('start_time', [$primerDia->copy()->startOfDay(), $ultimoDia->copy()->endOfDay()])
            ->orderBy('start_time')
            ->get();
        
        // Agrupar reservas por día
        $reservasPorDia = $reservas->groupBy(function($reserva) {
            return Carbon::parse($reserva->start_time)->format('Y-m-d');
        });
        
        // Construir el calendario
        $calendario = [];
        $diaActual = $primerDia->copy();
        
        // Retroceder al lunes de la primera semana
        while ($diaActual->dayOfWeekIso != 1) {
            $diaActual->subDay();
        }
        
        // Generar 6 semanas (para cubrir todos los casos)
        for ($semana = 0; $semana < 6; $semana++) {
            $calendario[$semana] = [];
            for ($dia = 0; $dia < 7; $dia++) {
                $calendario[$semana][$dia] = [
                    'numero' => $diaActual->day,
                    'fecha' => $diaActual->format('Y-m-d'),
                    'mes_actual' => $diaActual->month == $mes,
                    'es_hoy' => $diaActual->isToday()
                ];
                $diaActual->addDay();
            }
        }
        
        return view('reservas.calendario', compact(
            'calendario',
            'reservasPorDia',
            'mes',
            'año',
            'mesNombre',
            'mesPrev',
            'añoPrev',
            'mesNext',
            'añoNext'
        ));
    }
}
