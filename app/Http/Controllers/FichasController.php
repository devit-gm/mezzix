<?php

namespace App\Http\Controllers;

use App\Models\Ajustes;
use App\Models\Ficha;
use App\Models\FichaGasto;
use App\Models\FichaUsuario;
use Illuminate\Http\Request;
use App\Models\Servicio;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\User;
use App\Models\FichaServicio;
use App\Models\FichaProducto;
use Illuminate\Support\Facades\File;
use App\Models\Familia;
use App\Models\FichaRecibo;
use App\Models\Producto;
use App\Models\Site;
use Ramsey\Uuid\Type\Decimal;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Jobs\NotificarStockBajo;
use Barryvdh\DomPDF\Facade\Pdf;

class FichasController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $domain = $request->getHost();
            $site = Site::where('dominio', $domain)->first();
            
            if (!$site) {
                abort(404, 'Sitio no encontrado.');
            }
            
            if ($site->central == 1) {
                abort(403, 'No tiene acceso a este recurso.');
            }
            
            return $next($request);
        });
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Carbon::setLocale(app()->getLocale());

        $site = app('site');
        $ajustes = DB::connection('site')->table('ajustes')->first();

        // Redirigir a mesas si el modo operación es 'mesas'
        if ($ajustes && $ajustes->modo_operacion === 'mesas') {
            return redirect()->route('mesas.index');
        }

        // Redirigir a eventos/gestion si el modo operación es 'agencia_eventos' 
        // pero solo si no estamos ya en esa ruta
        if ($ajustes && $ajustes->modo_operacion === 'agencia_eventos') {
            if (!$request->is('eventos/gestion/*') && !$request->is('eventos/gestion')) {
                return redirect()->route('eventos.gestion.index');
            }
        }

// Consulta principal (solo una)
$query = Ficha::query()
    ->with(['usuario', 'inscritos']);   // 🔥 Eager loading

if ($request->method() == "GET") {
    $query->where('estado', 0)
          ->orderBy('fecha', 'asc')
          ->orderBy('hora', 'asc');
} else {
    if ($request->incluir_cerradas == 0) {
        $query->where('estado', 0)
              ->orderBy('fecha', 'asc')
              ->orderBy('hora', 'asc');
    } else {
        // Fichas cerradas: orden descendente (más recientes primero)
        $query->where('estado', 1)
              ->orderBy('fecha', 'desc')
              ->orderBy('hora', 'desc');
    }
}

$fichasMostrar = $query->get();

// FILTRO DE FICHAS
$fichas = [];
$user = Auth::user();

foreach ($fichasMostrar as $ficha) {

    $esAdmin = $user && $user->role_id == 1;
    $esPropietario = Auth::id() == $ficha->user_id;
    // No modificar la colección inscritos aquí - usar contains() que no modifica
    $estaEnFicha = $ficha->inscritos->contains('user_id', Auth::id());

    if ($ficha->tipo != 4) {
        if ($esPropietario || $esAdmin || $estaEnFicha) {
            $fichas[] = $ficha;
        }
    } else {
        $fichas[] = $ficha; // Eventos → todos los pueden ver
    }
}

// PROCESAR FICHAS
foreach ($fichas as $ficha) {

    $fecha = Carbon::parse($ficha->fecha);
    $ficha->mes = substr($fecha->translatedFormat('F'), 0, 3);

    // Solo calcular el precio si NO es un evento de agencia (tipo 4 en modo agencia_eventos)
    // En eventos de agencia, el precio viene del campo precio de la tabla
    if (!($ajustes && $ajustes->modo_operacion === 'agencia_eventos' && $ficha->tipo == 4)) {
        $ficha->precio = $this->ObtenerImporteFicha($ficha);
    }

    // Borrable
    $esAdmin = $user && $user->role_id == 1;
    $esPropietario = Auth::id() == $ficha->user_id;
    // No modificar la colección inscritos aquí - usar contains() que no modifica
    $estaEnFicha = $ficha->inscritos->contains('user_id', Auth::id());

    $ficha->borrable = ($esPropietario || $esAdmin || $estaEnFicha) && $ficha->estado == 0;

    // Calcular comensales
    $usuariosFicha = ($ficha->tipo == 1)
        ? collect([$ficha->usuario])->filter()
        : $ficha->inscritos;

    $ficha->total_comensales = $usuariosFicha->sum(fn($u) => 1 + ($u->invitados ?? 0) + ($u->ninos ?? 0));
    $ficha->total_ninos      = $usuariosFicha->sum(fn($u) => $u->ninos ?? 0);
    
    // Agregar información de si el usuario está apuntado (para eventos tipo 4)
    if ($ficha->tipo == 4) {
        // Consulta directa a la base de datos para eventos tipo 4
        $ficha->apuntado = FichaUsuario::where('id_ficha', $ficha->uuid)
            ->where('user_id', Auth::id())
            ->first();
        
        // Si la relación inscritos está vacía, recargarla para el cálculo de totales
        if ($ficha->inscritos->isEmpty()) {
            $inscritosReales = FichaUsuario::where('id_ficha', $ficha->uuid)->get();
            $ficha->total_comensales = $inscritosReales->sum(fn($u) => 1 + ($u->invitados ?? 0) + ($u->ninos ?? 0));
            $ficha->total_ninos = $inscritosReales->sum(fn($u) => $u->ninos ?? 0);
        }
        
        // En modo agencia_eventos, actualizar inscritos_actuales
        if ($ajustes && $ajustes->modo_operacion === 'agencia_eventos') {
            $ficha->inscritos_actuales = $ficha->total_comensales;
        }
    }
}

// Si son fichas cerradas, limitar a 20 más recientes después del filtro de permisos
if ($request->method() == "POST" && $request->incluir_cerradas == 1) {
    $fichas = array_slice($fichas, 0, 20);
}

        $errors = new \Illuminate\Support\MessageBag();
        if ($fichas == null || count($fichas) == 0) {
            $mensajeError = ($ajustes && $ajustes->modo_operacion === 'agencia_eventos') 
                ? __('No se encontraron eventos para mostrar.')
                : __('No se encontraron fichas para mostrar.');
            $errors->add('msg', $mensajeError);
            return view('fichas.index', compact('fichas', 'errors', 'request', 'ajustes'));
        } else {
            return view('fichas.index', compact('fichas', 'request', 'ajustes'));
        }    }

    /**
     * Store a newly createdStore a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'descripcion' => 'max:255',
            'fecha' => 'required|date',
            'tipo' => 'required',
            'foto_evento' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048'
        ]);

        $descripcion = '';
        if ($request->descripcion == null) {
            $descripcion = '';
        } else {
            $descripcion = $request->descripcion;
        }

        // Manejar subida de foto del evento
        $fotoEvento = null;
        if ($request->hasFile('foto_evento')) {
            $fotoEvento = $request->file('foto_evento')->store('eventos', 'public');
        }

        // ...
        $uuid = (string) Uuid::uuid4();
        $ficha = Ficha::create([
            'uuid' => $uuid,
            'descripcion' => $descripcion,
            'user_id' => $request->user_id,
            'precio' => $request->precio,
            'invitados_grupo' => $request->invitados_grupo,
            'estado' => $request->estado,
            'tipo' => $request->tipo,
            'fecha' => $request->fecha,
            'hora' => $request->hora,
            'menu' => $request->menu,
            'responsables' => $request->responsables,
            'foto_evento' => $fotoEvento,
            'descripcion_evento' => $request->descripcion_evento,
            'ubicacion_evento' => $request->ubicacion_evento,
            'aforo_maximo' => $request->aforo_maximo,
            'inscritos_actuales' => 0
        ]);
        
        // Si es un evento (tipo 4) y estamos en modo agencia, notificar a usuarios
        $ajustes = \DB::connection('site')->table('ajustes')->first();
        if ($request->tipo == 4 && $ajustes && $ajustes->modo_operacion === 'agencia_eventos') {
            $this->notificarNuevoEvento($ficha);
        }
        
        if ($request->tipo == 1 || $request->tipo == 2) {
            return redirect()->route('fichas.familias', ['uuid' => $ficha->uuid]);
        } else {
            if ($request->tipo == 4) {
                return redirect()->route('fichas.usuarios', ['uuid' => $ficha->uuid]);
            } else {
                return redirect()->route('fichas.gastos', ['uuid' => $ficha->uuid]);
            }
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
        $ficha = Ficha::find($uuid);
        if ($ficha->user_id == Auth::id() || (Auth::check() && Auth::user()->role_id == 1) || FichaUsuario::where('id_ficha', $ficha->uuid)->where('user_id', Auth::id())->first()) {
            $ficha->borrable = true;
        } else {
            $ficha->borrable = false;
        }
        $ficha->precio = $this->ObtenerImporteFicha($ficha);
        $fechaCambiada = Carbon::parse($ficha->fecha)->todateTimeString();
        return view('fichas.edit', compact('ficha', 'fechaCambiada'));
    }

        /**
     * Enviar productos de la ficha a cocina (modo mesas)
     */
    public function enviarCocina($uuid)
    {
        $ficha = Ficha::find($uuid);
        if (!$ficha) {
            return redirect()->back()->with('error', __('Ficha no encontrada.'));
        }
        // Cambiar estado de productos solo si estado es NULL
        $productos = FichaProducto::with('producto.familiaObj')->where('id_ficha', $uuid)->whereNull('estado')->get();
        foreach ($productos as $producto) {
            // Cargar el producto y su familia
            $productoModel = $producto->producto;
            $familia = $productoModel && $productoModel->familiaObj ? $productoModel->familiaObj : null;
            if ($familia && $familia->mostrar_en_cocina) {
                $producto->estado = 'pendiente';
            } else {
                $producto->estado = 'preparado';
            }
            $producto->save();
        }
        return redirect()->route('fichas.lista', $uuid)
            ->with('success', __('Artículos enviados a cocina.'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $uuid)
    {
        $request->validate([
            'descripcion' => 'max:255',
            'fecha' => 'required|date',
            'tipo' => 'required',
            'foto_evento' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048'
        ]);
        $ficha = Ficha::find($uuid);

        if ($request->descripcion == null) {
            $ficha->descripcion = '';
        } else {
            $ficha->descripcion = $request->descripcion;
        }
        $descripcion = $ficha->descripcion;

        // Manejar subida de nueva foto del evento
        if ($request->hasFile('foto_evento')) {
            // Eliminar foto anterior si existe
            if ($ficha->foto_evento && \Storage::disk('public')->exists($ficha->foto_evento)) {
                \Storage::disk('public')->delete($ficha->foto_evento);
            }
            $ficha->foto_evento = $request->file('foto_evento')->store('eventos', 'public');
        }

        $ficha->update([
            'descripcion' => $descripcion,
            'user_id' => $request->user_id,
            'precio' =>  $request->precio ?? $this->ObtenerImporteFicha($ficha),
            'invitados_grupo' => $request->invitados_grupo,
            'estado' => $request->estado,
            'tipo' => $request->tipo,
            'fecha' => $request->fecha,
            'hora' => $request->hora,
            'menu' => $request->menu,
            'responsables' => $request->responsables,
            'foto_evento' => $ficha->foto_evento,
            'descripcion_evento' => $request->descripcion_evento,
            'ubicacion_evento' => $request->ubicacion_evento,
            'aforo_maximo' => $request->aforo_maximo
        ]);
        
        $ajustes = \DB::connection('site')->table('ajustes')->first();
        if ($ajustes && $ajustes->modo_operacion === 'agencia_eventos') {
            return redirect()->route('eventos.gestion.index')
                ->with('success', __('Evento actualizado con éxito.'));
        }
        
        return redirect()->route('fichas.index')
            ->with('success', __('Ficha actualizada con éxito.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
    {
        // Eliminar archivos de tickets antes de borrar registros
        $fichaGastos = FichaGasto::where('id_ficha', $uuid)->get(['ticket']);
        foreach ($fichaGastos as $fichaGasto) {
            if ($fichaGasto->ticket && File::exists(public_path('images') . '/'  . $fichaGasto->ticket)) {
                File::delete(public_path('images') . '/'  . $fichaGasto->ticket);
            }
        }
        
        // Eliminación masiva con una sola query cada una
        FichaProducto::where('id_ficha', $uuid)->delete();
        FichaServicio::where('id_ficha', $uuid)->delete();
        FichaUsuario::where('id_ficha', $uuid)->delete();
        FichaGasto::where('id_ficha', $uuid)->delete();
        
        $ficha = Ficha::find($uuid);
        $ficha->delete();
        
        return redirect()->route('fichas.index')
            ->with('success', __('Ficha eliminada con éxito'));
    }

    /**
     * Show the form for creating a new post.
     */
    public function create()
    {
        $userId = Auth::id();
        $userTimezone = 'Europe/Madrid';
        $currentDateTime = Carbon::now($userTimezone);
        $ajustes = app('ajustes');
        return view('fichas.create', compact('userId', 'currentDateTime', 'ajustes'));
    }

    public function download(string $uuid)
    {
        $ficha = Ficha::with(['productos.producto', 'servicios.servicio', 'camarero'])
            ->where('uuid', $uuid)
            ->first();
        
        if (!$ficha) {
            return redirect()->back()->with('error', 'Ficha no encontrada');
        }
        
        $ficha->precio = $this->ObtenerImporteFicha($ficha);
        $fechaCambiada = Carbon::parse($ficha->fecha)->todateString();
        
        // Si es una mesa, usar la vista PDF específica para mesas
        if ($ficha->modo === 'mesa') {
            $pdf = PDF::loadView('fichas.pdf-mesa', compact('ficha', 'fechaCambiada'));
            return $pdf->download('mesa_' . $ficha->numero_mesa . '_' . date('Ymd') . '.pdf');
        }
        
        // Para fichas normales, usar la vista original
        $pdf = PDF::loadView('fichas.pdf', compact('ficha', 'fechaCambiada'));
        return $pdf->download('ficha_' . $ficha->uuid . '.pdf');
    }

    /**
     * Show the form for editing the specified post.
     *
     * @param  int  $uuid
     */
    public function edit(string $uuid)
    {
        $ficha = Ficha::where('uuid', $uuid)->firstOrFail();
        
        // Obtener ajustes para verificar el modo
        $ajustes = app('ajustes');
        
        // Solo calcular el precio si NO estamos en modo agencia de eventos
        // En modo agencia, el precio viene directamente del campo precio de la tabla
        if ($ajustes->modo_operacion !== 'agencia_eventos' || $ficha->tipo != 4) {
            $ficha->precio = $this->ObtenerImporteFicha($ficha);
        }
        
        $fechaCambiada = Carbon::parse($ficha->fecha)->todateString();
        if ($ficha->user_id == Auth::id() || (Auth::check() && Auth::user()->role_id == 1) || FichaUsuario::where('id_ficha', $ficha->uuid)->where('user_id', Auth::id())->exists()) {
            $ficha->borrable = true;
        } else {
            $ficha->borrable = false;
        }

        $userTimezone = 'Europe/Madrid';
        $currentDateTime = Carbon::now($userTimezone);
        return view('fichas.edit', compact('ficha', 'fechaCambiada', 'currentDateTime', 'ajustes'));
    }

    private function ObtenerImporteFicha($ficha, $sumarInvitados = false)
    {
        // Usar ajustes cacheados si están disponibles
        $ajustes = app()->has('ajustes') ? app('ajustes') : Ajustes::first();
        
        // Validar que ajustes existe
        if (!$ajustes) {
            Log::warning('Ajustes no encontrado en ObtenerImporteFicha', ['ficha_uuid' => $ficha->uuid]);
            $ajustes = new Ajustes(); // Crear objeto vacío para evitar errores
        }
        
        // Usar sum() en lugar de loops para mejor rendimiento
        $precio = FichaProducto::where('id_ficha', $ficha->uuid)->sum('precio');
        $precio += FichaServicio::where('id_ficha', $ficha->uuid)->sum('precio');
        $precio += FichaGasto::where('id_ficha', $ficha->uuid)->sum('precio');
        
        // Solo procesar invitados si es necesario
        if ($sumarInvitados && $ajustes->uuid) {
            $usuarios = FichaUsuario::where('id_ficha', $ficha->uuid)->get(['invitados']);
            foreach ($usuarios as $usuario) {
                $num_invitados = $usuario->invitados;
                if ($num_invitados > ($ajustes->max_invitados_cobrar ?? 0)) {
                    $num_invitados = $ajustes->max_invitados_cobrar ?? 0;
                }
                if (($ajustes->primer_invitado_gratis ?? false) && $num_invitados > 0) {
                    $num_invitados--;
                }
                $precio += $num_invitados * ($ajustes->precio_invitado ?? 0);
            }
        }
        
        if (($ajustes->activar_invitados_grupo ?? false) && $ficha->invitados_grupo > 0) {
            $precio += $ficha->invitados_grupo;
        }
        
        return $precio;
    }

    public function familias(string $uuid)
    {
        $ficha = Ficha::find($uuid);
        $ficha->precio = $this->ObtenerImporteFicha($ficha); 
        $familias = Familia::orderBy('posicion')->get();
        $ajustes = DB::connection('site')->table('ajustes')->first();
        //Si no es un evento y el usuario activo no está en la ficha lo añadimos
        if ($ficha->tipo != 4) {
            $estaUsuarioActivo = FichaUsuario::where('id_ficha', $ficha->uuid)->where('user_id', Auth::id())->first();
            if (!$estaUsuarioActivo) {
                //Si el usuario activo no está en la ficha lo añadimos
                FichaUsuario::create([
                    'uuid' => (string) Uuid::uuid4(),
                    'id_ficha' => $ficha->uuid,
                    'user_id' => Auth::id(),
                    'invitados' => 0
                ]);
            }
        }
        //Si es un evento no hacemos nada
        //Para que los usuarios puedan apuntarse o no
        return view('fichas.familias', compact('ficha', 'familias', 'ajustes'));
    }

    public function buscarPorBarcode(Request $request)
    {
        $request->validate([
            'ean13' => 'required|string|max:50',
            'ficha_uuid' => 'required|string'
        ]);

        $ean13 = $request->input('ean13');
        $fichaUuid = $request->input('ficha_uuid');

        // Buscar producto por código EAN13
        $producto = Producto::where('ean13', $ean13)->first();

        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => __('Producto no encontrado con código: ') . $ean13
            ], 404);
        }

        // Verificar que la ficha existe
        $ficha = Ficha::find($fichaUuid);
        if (!$ficha) {
            return response()->json([
                'success' => false,
                'message' => __('Ficha no encontrada')
            ], 404);
        }

        // Añadir producto a la ficha
        $fichaProducto = FichaProducto::where('id_ficha', $fichaUuid)
            ->where('id_producto', $producto->uuid)
            ->first();

        if ($fichaProducto) {
            // Si ya existe, incrementar cantidad
            $fichaProducto->cantidad += 1;
            $fichaProducto->precio = $fichaProducto->cantidad * $producto->precio;
            $fichaProducto->save();
        } else {
            // Si no existe, crear nuevo registro
            FichaProducto::create([
                'uuid' => (string) Uuid::uuid4(),
                'id_ficha' => $fichaUuid,
                'id_producto' => $producto->uuid,
                'cantidad' => 1,
                'precio' => $producto->precio,
                'borrable' => true
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => __('Producto añadido: ') . $producto->nombre,
            'producto' => $producto,
            'redirect_url' => route('fichas.lista', ['uuid' => $fichaUuid]) . '?success=' . urlencode(__('Producto añadido: ') . $producto->nombre)
        ]);
    }

    public function productos($uuid, $uuid2)
    {
        $ficha = Ficha::find($uuid);
        $ficha->precio = $this->ObtenerImporteFicha($ficha);
        $familia = Familia::find($uuid2);
        $ajustes = DB::connection('site')->table('ajustes')->first();
        $stockMinimo = $ajustes->stock_minimo ?? 5;
        
        // OPTIMIZADO: Cache solo de la lista de productos (estructura, no stock)
        // El stock se verifica SIEMPRE en tiempo real
        $cacheKey = "productos_familia_{$uuid2}";
        
        $productos = Cache::remember($cacheKey, 300, function() use ($uuid2) {
            return Producto::where('familia', $uuid2)
                ->where(function($query) {
                    $query->where('precio', '>', 0)
                          ->orWhere('combinado', 1);
                })
                ->orderBy('posicion')
                ->get();
        });
        
        if ($ajustes->permitir_comprar_sin_stock == 1) {
            $productosAgotados = collect();
            $productosStockBajo = collect();
        } else {
            // Obtener UUIDs de los productos de esta familia para optimizar queries
            $productosUuids = $productos->pluck('uuid');
            
            // OPTIMIZADO: Productos simples agotados (stock disponible <= 0)
            $agotadosSimples = DB::connection('site')
                ->table('productos')
                ->whereIn('uuid', $productosUuids)
                ->where('combinado', 0)
                ->whereRaw('(stock - COALESCE(stock_reservado, 0)) <= 0')
                ->pluck('uuid');
            
            // OPTIMIZADO: Productos combinados con algún componente agotado
            $agotadosCombinados = DB::connection('site')
                ->table('productos as p')
                ->join('composicion_productos as cp', 'p.uuid', '=', 'cp.id_producto')
                ->join('productos as componente', 'cp.id_componente', '=', 'componente.uuid')
                ->whereIn('p.uuid', $productosUuids)
                ->where('p.combinado', 1)
                ->whereRaw('(componente.stock - COALESCE(componente.stock_reservado, 0)) <= 0')
                ->distinct()
                ->pluck('p.uuid');
            
            $idsAgotados = $agotadosSimples->merge($agotadosCombinados)->unique();
            $productosAgotados = $productos->whereIn('uuid', $idsAgotados);
            
            // OPTIMIZADO: Productos simples con stock bajo (0 < stock disponible <= stock_minimo)
            $stockBajoSimples = DB::connection('site')
                ->table('productos')
                ->whereIn('uuid', $productosUuids)
                ->where('combinado', 0)
                ->whereRaw('(stock - COALESCE(stock_reservado, 0)) > 0')
                ->whereRaw('(stock - COALESCE(stock_reservado, 0)) <= ?', [$stockMinimo])
                ->whereNotIn('uuid', $idsAgotados)
                ->pluck('uuid');
            
            // OPTIMIZADO: Productos combinados con algún componente con stock bajo
            $stockBajoCombinados = DB::connection('site')
                ->table('productos as p')
                ->join('composicion_productos as cp', 'p.uuid', '=', 'cp.id_producto')
                ->join('productos as componente', 'cp.id_componente', '=', 'componente.uuid')
                ->whereIn('p.uuid', $productosUuids)
                ->where('p.combinado', 1)
                ->whereRaw('(componente.stock - COALESCE(componente.stock_reservado, 0)) > 0')
                ->whereRaw('(componente.stock - COALESCE(componente.stock_reservado, 0)) <= ?', [$stockMinimo])
                ->whereNotIn('p.uuid', $idsAgotados)
                ->distinct()
                ->pluck('p.uuid');
            
            $idsStockBajo = $stockBajoSimples->merge($stockBajoCombinados)->unique();
            $productosStockBajo = $productos->whereIn('uuid', $idsStockBajo);
        }
        
        return view('fichas.productos', compact('ficha', 'familia', 'productos', 'productosAgotados', 'productosStockBajo', 'ajustes'));
    }

    public function usuarios($uuid)
    {
        $ficha = Ficha::find($uuid);
        $ajustes = DB::connection('site')->table('ajustes')->first();
     
        $ficha->precio = $this->ObtenerImporteFicha($ficha);
        $site = app('site');
        
        // Si es modo agencia de eventos (tipo 4), solo mostrar usuarios inscritos
        $esAgenciaEventos = ($ajustes->modo_operacion === 'agencia_eventos' && $ficha->tipo == 4);
        
        if ($esAgenciaEventos) {
            // En modo agencia, solo mostrar usuarios que están en fichas_usuarios
            $usuariosInscritos = DB::connection('site')
                ->table('fichas_usuarios')
                ->where('id_ficha', $uuid)
                ->pluck('user_id');
            
            $usuariosFicha = User::where('site_id', $site->id)
                ->whereIn('id', $usuariosInscritos)
                ->orderBy('id')
                ->get();
        } elseif ($ficha->tipo == 1) {
            //Si es una ficha individual sólo mostramos al usuario activo
            $usuariosFicha = User::where('site_id', $site->id)->where('id', $ficha->user_id)->get();
        } else {
            $usuariosFicha = User::where('site_id', $site->id)->orderBy('id')->get();
        }
        
        //Si la ficha está cerrada (estado = 1) solo mostramos los usuarios que están en FichaUsuario
        if ($ficha->estado == 1 && !$esAgenciaEventos) {
            $usuariosFicha = [];
            //Buscar los usuarios que están dentro de FichaUsuario
            $usuarios = User::where('site_id', $site->id)->orderBy('id')->get();
            $fichasUsuariosIds = FichaUsuario::where('id_ficha', $ficha->uuid)
                ->pluck('user_id')
                ->flip();
            foreach ($usuarios as $usuario) {
                //si el user_id está en FichaUsuario de la ficha lo ponemos como marcado
                if (isset($fichasUsuariosIds[$usuario->id])) {
                    $usuariosFicha[] = $usuario;
                }
            }
        }

        $total_comensales = 0;
        
        $fichasUsuariosData = FichaUsuario::where('id_ficha', $ficha->uuid)
            ->get()
            ->keyBy('user_id');

        foreach ($usuariosFicha as $usuarioFicha) {
            //si el user_id está en FichaUsuario de la ficha lo ponemos como marcado
            $fichaUsuario = $fichasUsuariosData->get($usuarioFicha->id);
            if ($fichaUsuario) {
                $usuarioFicha->marcado = true;
                $usuarioFicha->invitados = $fichaUsuario->invitados;
                $usuarioFicha->ninos = $fichaUsuario->ninos;
                $usuarioFicha->created_at = $fichaUsuario->created_at; // Fecha de inscripción
                $total_comensales += $fichaUsuario->invitados;
                $total_comensales += $fichaUsuario->ninos;
                $total_comensales++;
            } else {
                $usuarioFicha->marcado = false;
                $usuarioFicha->invitados = 0;
                $usuarioFicha->ninos = 0;
            }
        }


        $ficha->total_comensales = $total_comensales;
        return view('fichas.usuarios', compact('ficha', 'usuariosFicha','ajustes'));
    }

    public function resumen($uuid)
    {
        $ficha = Ficha::with(['productos.producto', 'servicios.servicio', 'usuarios', 'gastos'])->find($uuid);
        $ficha->precio = $this->ObtenerImporteFicha($ficha);
        
        $total_consumos = $ficha->productos->sum('precio');
        $ficha->total_consumos = $total_consumos;
        
        $total_servicios = $ficha->servicios->sum('precio');
        $ficha->total_servicios = $total_servicios;

        $total_comensales = 0;
        $total_ninos = 0;
        if ($ficha->tipo == 3) {
            $total_comensales = 1;
        } else {
            $total_invitados = $ficha->usuarios->sum('invitados');
            $total_ninos = $ficha->usuarios->sum('ninos');
            $total_comensales = $ficha->usuarios->count() + $total_invitados + $total_ninos;
        }
        // De momento los invitados de grupo no cuentan
        // if ($ficha->invitados_grupo > 0) {
        //     $total_comensales += $ficha->invitados_grupo;
        // }
        $ficha->total_comensales = $total_comensales - $total_ninos;
        
        $total_gastos = $ficha->gastos->sum('precio');
        $ficha->total_gastos = $total_gastos;
        if ($total_comensales == 0) {
            $ficha->precio_comensal = 0;
        } else {
            $ficha->precio_comensal = $ficha->precio / ($total_comensales - $total_ninos);
        }
        
        // Calcular desglose de IVA
        $ivaDesglose = [];
        $totalBaseImponible = 0;
        $totalIva = 0;
        
        // IVA de productos
        foreach ($ficha->productos as $fp) {
            if ($fp->producto) {
                $iva = $fp->producto->iva ?? 21;
                $pvp = $fp->precio;
                $baseImponible = $pvp / (1 + $iva / 100);
                $cuotaIva = $pvp - $baseImponible;
                
                $ivaKey = number_format($iva, 2);
                if (!isset($ivaDesglose[$ivaKey])) {
                    $ivaDesglose[$ivaKey] = ['porcentaje' => $iva, 'base' => 0, 'cuota' => 0];
                }
                $ivaDesglose[$ivaKey]['base'] += $baseImponible;
                $ivaDesglose[$ivaKey]['cuota'] += $cuotaIva;
                
                $totalBaseImponible += $baseImponible;
                $totalIva += $cuotaIva;
            }
        }
        
        // IVA de servicios
        foreach ($ficha->servicios as $fs) {
            if ($fs->servicio) {
                $iva = $fs->servicio->iva ?? 21;
                $pvp = $fs->precio;
                $baseImponible = $pvp / (1 + $iva / 100);
                $cuotaIva = $pvp - $baseImponible;
                
                $ivaKey = number_format($iva, 2);
                if (!isset($ivaDesglose[$ivaKey])) {
                    $ivaDesglose[$ivaKey] = ['porcentaje' => $iva, 'base' => 0, 'cuota' => 0];
                }
                $ivaDesglose[$ivaKey]['base'] += $baseImponible;
                $ivaDesglose[$ivaKey]['cuota'] += $cuotaIva;
                
                $totalBaseImponible += $baseImponible;
                $totalIva += $cuotaIva;
            }
        }
        
        ksort($ivaDesglose);

        
        $ajustes = Ajustes::first();
        
        return view('fichas.resumen', compact('ficha', 'ajustes', 'ivaDesglose', 'totalBaseImponible', 'totalIva'));
    }

    public function enviar($uuid)
    {
        $ficha = Ficha::find($uuid);
        $ficha->precio = $this->ObtenerImporteFicha($ficha,true);
        $gastosFicha = FichaGasto::where('id_ficha', $uuid)->get();
        $ajustes = DB::connection('site')->table('ajustes')->first();
        //Insertamos en la tabla ficha_recibos los gastos de la ficha
        foreach ($gastosFicha as $gastoFicha) {
            FichaRecibo::create([
                'uuid' => (string) Uuid::uuid4(),
                'id_ficha' => $uuid,
                'user_id' => $gastoFicha->user_id,
                'tipo' => 2,
                'estado' => 0,
                'precio' => $gastoFicha->precio,
                'fecha' => Carbon::now()
            ]);
        }
        if ($ficha->tipo != 3) {
            //Obtenemos el precio total por comensal
            $total_comensales = 0;
            $usuarios = FichaUsuario::where('id_ficha', $uuid)->get();
            foreach ($usuarios as $usuario) {
                $total_comensales += $usuario->invitados;
                $total_comensales++;
            }

            // De momento los invitados de grupo no cuentan
            // if ($ficha->invitados_grupo > 0) {
            //     $total_comensales += $ficha->invitados_grupo;
            // }
            $precio_comensal = $ficha->precio / $total_comensales;
            //Insertamos en la tabla ficha_recibos el gasto por comensal
            //Que es el precio por comensal * número de invitados de cada usuario
            //Hay que añadir el gasto del propio comensal
            foreach ($usuarios as $usuario) {
                $num_invitados = $usuario->invitados;

                //si en la configuración del sitio las fichas se facturan de forma automática el estado se pone a 1

                FichaRecibo::create([
                    'uuid' => (string) Uuid::uuid4(),
                    'id_ficha' => $uuid,
                    'user_id' => $usuario->user_id,
                    'tipo' => 1,
                    'estado' => $ajustes->facturar_ficha_automaticamente ? 1 : 0,
                    'precio' => $precio_comensal * ($num_invitados + 1),
                    'fecha' => Carbon::now()
                ]);
            }

            //Descontamos el stock de cada artículo consumido y liberamos las reservas
            // OPTIMIZADO: Eager loading completo para evitar N+1 queries
            $productos = FichaProducto::with([
                'producto' => function($query) {
                    $query->select('uuid', 'nombre', 'precio', 'stock', 'stock_reservado', 'combinado', 'iva');
                },
                'producto.composicion.componenteProducto' => function($query) {
                    $query->select('uuid', 'nombre', 'stock', 'stock_reservado');
                }
            ])->where('id_ficha', $uuid)->get();
            
            Log::info('=== INICIO CONFIRMACIÓN VENTA (Stock real - Liberar reservas) ===', [
                'ficha_uuid' => $uuid,
                'productos_count' => $productos->count()
            ]);
            
            $stockService = new \App\Services\StockNotificationService();
            
            foreach ($productos as $producto) {
                $productoFicha = $producto->producto;
                if (!$productoFicha) continue;
                
                if (!$productoFicha) {
                    Log::warning('Producto no encontrado al confirmar venta', [
                        'ficha_producto_id' => $producto->id
                    ]);
                    continue;
                }
                
                if ($productoFicha->combinado == 1) {
                    foreach ($productoFicha->composicion as $composicion) {
                        $producto2 = $composicion->componenteProducto;
                        if ($producto2) {
                            // Usar el método confirmarVenta que descuenta stock real y libera reserva
                            $stockAnterior = $producto2->stock;
                            $reservaAnterior = $producto2->stock_reservado;
                            $producto2->confirmarVenta($producto->cantidad);
                            
                            Log::info('Venta confirmada (componente)', [
                                'producto' => $producto2->nombre,
                                'stock_anterior' => $stockAnterior,
                                'stock_nuevo' => $producto2->fresh()->stock,
                                'reserva_anterior' => $reservaAnterior,
                                'reserva_nueva' => $producto2->fresh()->stock_reservado,
                                'cantidad_vendida' => $producto->cantidad
                            ]);
                            
                            // OPTIMIZADO: Verificar stock bajo de forma asíncrona
                            NotificarStockBajo::dispatch($producto2->uuid)->afterCommit();
                        }
                    }
                } else {
                    // Usar el método confirmarVenta que descuenta stock real y libera reserva
                    $stockAnterior = $productoFicha->stock;
                    $reservaAnterior = $productoFicha->stock_reservado;
                    $productoFicha->confirmarVenta($producto->cantidad);
                    
                    Log::info('Venta confirmada', [
                        'producto' => $productoFicha->nombre,
                        'stock_anterior' => $stockAnterior,
                        'stock_nuevo' => $productoFicha->fresh()->stock,
                        'reserva_anterior' => $reservaAnterior,
                        'reserva_nueva' => $productoFicha->fresh()->stock_reservado,
                        'cantidad_vendida' => $producto->cantidad
                    ]);
                    
                    // OPTIMIZADO: Verificar stock bajo de forma asíncrona
                    NotificarStockBajo::dispatch($productoFicha->uuid)->afterCommit();
                }
            }
            
            Log::info('=== FIN CONFIRMACIÓN VENTA ===');
        }
        $ficha->estado = 1;
        $ficha->save();
        return redirect()->route('fichas.index')
            ->with('success', __('Ficha enviada con éxito'));
    }

    public function gastos($uuid)
    {
        $ajustes = Ajustes::first();
        $ficha = Ficha::find($uuid);
        $ficha->precio = $this->ObtenerImporteFicha($ficha);
        $gastosFicha = FichaGasto::with('usuario')->where('id_ficha', $uuid)->get();
        foreach ($gastosFicha as $gastoFicha) {
            $gastoFicha->borrable = true;
        }

        //Si es una ficha de compra ha llegado directamente
        //Hay que comprobar si el usuario activo está en la ficha
        //El usuario activo tiene que ser el usuario de la ficha
        if ($ficha->tipo == 3 && $ficha->user_id == Auth::id()) {
            $estaUsuarioActivo = FichaUsuario::where('id_ficha', $ficha->uuid)->where('user_id', Auth::id())->first();
            if (!$estaUsuarioActivo) {
                //Si el usuario activo no está en la ficha lo añadimos
                FichaUsuario::create([
                    'uuid' => (string) Uuid::uuid4(),
                    'id_ficha' => $ficha->uuid,
                    'user_id' => Auth::id(),
                    'invitados' => 0
                ]);
            }
        }
        //Para el resto de tipos de ficha no hacemos nada ya que se 
        //controla en otro sitio.
        $errors = new \Illuminate\Support\MessageBag();
        if ($gastosFicha == null || count($gastosFicha) == 0) {
            $errors->add('msg', __('No se han introducido gastos.'));
            return view('fichas.gastos', compact('ficha', 'gastosFicha', 'errors', 'ajustes'));
        } else {
            return view('fichas.gastos', compact('ficha', 'gastosFicha', 'ajustes'));
        }
    }

    public function addgastos($uuid)
    {
        $site = app('site');
        $ficha = Ficha::find($uuid);
        $ficha->precio = $this->ObtenerImporteFicha($ficha);
        $usuariosFicha = FichaUsuario::where('id_ficha', $uuid)->get();
        if ($usuariosFicha->isEmpty()) {
            $usuariosFicha = User::where('id', $ficha->user_id)->get();
        } else {
            $usuariosArray = [];
            //Buscar los usuarios que están dentro de FichaUsuario
            $usuarios = User::where('site_id', $site->id)->orderBy('id')->get();
            foreach ($usuarios as $usuario) {
                //si el user_id está en FichaUsuario de la ficha lo ponemos como marcado
                $fichaUsuario = FichaUsuario::where('id_ficha', $ficha->uuid)->where('user_id', $usuario->id)->first();
                if ($fichaUsuario) {
                    $usuariosArray[] = $usuario;
                }
            }
            $usuariosFicha = collect($usuariosArray);
        }
        return view('fichas.addgastos', compact('ficha', 'usuariosFicha'));
    }

    public function destroygastos(string $uuid, string $uuid2)
    {

        //buscar en fichaproducto la ficha con id_ficha = uuid y id_producto = uuid2
        $fichaGastos = FichaGasto::where('id_ficha', $uuid)->where('uuid', $uuid2)->get();
        foreach ($fichaGastos as $fichaGasto) {
            if (File::exists(public_path('images') . '/'  . $fichaGasto->ticket)) {
                File::delete(public_path('images') . '/'  . $fichaGasto->ticket);
            }
            $fichaGasto->delete();
        }
        return redirect()->route('fichas.gastos', $uuid)
            ->with('success', __('Gasto eliminado de la ficha'));
    }

    public function updategastos($uuid, Request $request)
    {
        if ($request->ticket == null) {
            $request->validate([
                'descripcion' => 'max:255',
                'precio' => 'required'
            ]);
            $fichaGasto = FichaGasto::create([
                'uuid' => (string) Uuid::uuid4(),
                'id_ficha' => $uuid,
                'user_id' => Auth::id(),
                'descripcion' => $request->descripcion,
                'ticket' => '',
                'precio' => $request->precio
            ]);
        } else {
            $request->validate([
                'descripcion' => 'max:255',
                'ticket' => 'required|image|mimes:png,jpg,jpeg|max:20480',
                'precio' => 'required'
            ]);

            $imageName = time() . '.' . $request->ticket->extension();
            $request->ticket->move(public_path('images'), $imageName);

            $fichaGasto = FichaGasto::create([
                'uuid' => (string) Uuid::uuid4(),
                'id_ficha' => $uuid,
                'user_id' => Auth::id(),
                'descripcion' => $request->descripcion,
                'ticket' => $imageName,
                'precio' => $request->precio
            ]);
        }

        return redirect()->route('fichas.gastos', $uuid)->with('success', __('Gastos de la ficha actualizados con éxito'));
    }

    public function updateusuarios($uuid, Request $request)
    {
        $site = app('site');
        
        // Obtener datos completos de usuarios antes de la actualización (para detectar cambios)
        $usuariosAntes = FichaUsuario::where('id_ficha', $uuid)
            ->get()
            ->keyBy('user_id');
        
        // Eliminar todos los usuarios actuales
        FichaUsuario::where('id_ficha', $uuid)->delete();
        
        // Array para usuarios después de la actualización
        $usuariosDespues = [];
        
        if ($request->usuarios != null) {
            foreach ($request->usuarios as $usuario) {
                $idUsuario = intval(str_replace("]", "", str_replace("[", "", $usuario)));
                FichaUsuario::create([
                    'uuid' => (string) Uuid::uuid4(),
                    'id_ficha' => $uuid,
                    'user_id' => $idUsuario,
                    'invitados' => $request->invitados[$idUsuario] ?? 0,
                    'ninos' => $request->ninos[$idUsuario] ?? 0
                ]);
                $usuariosDespues[$idUsuario] = [
                    'invitados' => $request->invitados[$idUsuario] ?? 0,
                    'ninos' => $request->ninos[$idUsuario] ?? 0
                ];
            }
        }
        
        // Detectar cambios y enviar notificaciones (solo si es un evento - tipo 4)
        $ficha = Ficha::find($uuid);
        if ($ficha && $ficha->tipo == 4) {
            // Usuarios añadidos (están en después pero no en antes)
            $usuariosAnadidos = array_diff(array_keys($usuariosDespues), $usuariosAntes->pluck('user_id')->toArray());
            foreach ($usuariosAnadidos as $userId) {
                \App\Jobs\NotificarOrganizadorEvento::dispatch($uuid, $userId, 'inscripcion')
                    ->afterCommit();
            }
            
            // Usuarios eliminados (están en antes pero no en después)
            $usuariosEliminados = array_diff($usuariosAntes->pluck('user_id')->toArray(), array_keys($usuariosDespues));
            foreach ($usuariosEliminados as $userId) {
                \App\Jobs\NotificarOrganizadorEvento::dispatch($uuid, $userId, 'cancelacion')
                    ->afterCommit();
            }
            
            // Usuarios que cambiaron número de invitados o niños
            foreach ($usuariosDespues as $userId => $datosActuales) {
                if ($usuariosAntes->has($userId)) {
                    $datosAnteriores = $usuariosAntes->get($userId);
                    $invitadosAntes = $datosAnteriores->invitados;
                    $ninosAntes = $datosAnteriores->ninos;
                    $invitadosDespues = $datosActuales['invitados'];
                    $ninosDespues = $datosActuales['ninos'];
                    
                    // Si cambió el número de invitados o niños, enviar notificación
                    if ($invitadosAntes != $invitadosDespues || $ninosAntes != $ninosDespues) {
                        \App\Jobs\NotificarOrganizadorEvento::dispatch($uuid, $userId, 'actualizacion')
                            ->afterCommit();
                    }
                }
            }
        }
        
        $ficha->precio = $this->ObtenerImporteFicha($ficha);
        $usuariosFicha = User::where('site_id', $site->id)->orderBy('id')->get();
        foreach ($usuariosFicha as $usuarioFicha) {
            //si el user_id está en FichaUsuario de la ficha lo ponemos como marcado
            $fichaUsuario = FichaUsuario::where('id_ficha', $ficha->uuid)->where('user_id', $usuarioFicha->id)->first();
            if ($fichaUsuario) {
                $usuarioFicha->marcado = true;
                $usuarioFicha->invitados = $fichaUsuario->invitados;
                $usuarioFicha->ninos = $fichaUsuario->ninos;
            } else {
                $usuarioFicha->marcado = false;
                $usuarioFicha->invitados = 0;
                $usuarioFicha->ninos = 0;
            }
        }
        return redirect()->route('fichas.usuarios', compact('uuid'))->with('success', __('Usuarios de la ficha actualizados con éxito'));
    }

    public function updateservicios($uuid, Request $request)
    {
        FichaServicio::where('id_ficha', $uuid)->delete();
        if ($request->servicios != null) {
            foreach ($request->servicios as $servicio) {
                FichaServicio::create([
                    'uuid' => (string) Uuid::uuid4(),
                    'id_ficha' => $uuid,
                    'id_servicio' => $servicio,
                    'precio' => Servicio::find($servicio)->precio
                ]);
            }
        }
        $ficha = Ficha::find($uuid);
        $ficha->precio = $this->ObtenerImporteFicha($ficha);
        $serviciosFicha = Servicio::orderBy('nombre')->get();
        foreach ($serviciosFicha as $servicioFicha) {
            //si el id_servicio está en FichaServicio de la ficha lo ponemos como marcado
            $fichaServicio = FichaServicio::where('id_ficha', $ficha->uuid)->where('id_servicio', $servicioFicha->uuid)->first();
            if ($fichaServicio) {
                $servicioFicha->marcado = true;
            } else {
                $servicioFicha->marcado = false;
            }
        }
        return redirect()->route('fichas.servicios', compact('uuid'))->with('success', __('Servicios de la ficha actualizados con éxito'));
    }

    public function servicios($uuid)
    {
        $ficha = Ficha::find($uuid);
        $ficha->precio = $this->ObtenerImporteFicha($ficha);
        $serviciosFicha = Servicio::orderBy('nombre')->get();
        foreach ($serviciosFicha as $servicioFicha) {
            //si el id_servicio está en FichaServicio de la ficha lo ponemos como marcado
            $fichaServicio = FichaServicio::where('id_ficha', $ficha->uuid)->where('id_servicio', $servicioFicha->uuid)->first();
            if ($fichaServicio) {
                $servicioFicha->marcado = true;
            } else {
                $servicioFicha->marcado = false;
            }
        }
        $ajustes = Ajustes::first();
        return view('fichas.servicios', compact('ficha', 'serviciosFicha', 'ajustes'));
    }



    public function addproduct(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $ficha = Ficha::find($request->idFicha);
            $familia = Familia::find($request->idFamilia);
            $producto = Producto::with('composicion.componenteProducto')->find($request->idProducto);
            $cantidad = $request->cantidad;

            // Verificar stock disponible (solo si la ficha está abierta)
            if ($ficha->estado == 0) {
                if ($producto->combinado == 1) {
                    // Verificar stock de componentes (ya cargados con eager loading)
                    foreach ($producto->composicion as $composicion) {
                        $componente = $composicion->componenteProducto;
                        if ($componente && !$componente->tieneStockDisponible($cantidad)) {
                            return redirect()->back()
                                ->with('error', "Stock insuficiente de {$componente->nombre}. Disponible: {$componente->stock_disponible}");
                        }
                    }
                } else {
                    // Verificar stock del producto simple
                    if (!$producto->tieneStockDisponible($cantidad)) {
                        return redirect()->back()
                            ->with('error', "Stock insuficiente de {$producto->nombre}. Disponible: {$producto->stock_disponible}");
                    }
                }
            }

            //Si el producto es combinado hay que sumar el precio de sus componentes
            if ($producto->combinado == 1) {
                $precio = 0;
                foreach ($producto->composicion as $composicion) {
                    $componente = $composicion->componenteProducto;
                    $precio += $componente->precio;
                    
                    // Reservar stock del componente (solo si ficha abierta)
                    if ($ficha->estado == 0) {
                        $componente->reservarStock($cantidad);
                    }
                }
                $producto->precio = $precio;
            } else {
                // Reservar stock del producto simple (solo si ficha abierta)
                if ($ficha->estado == 0) {
                    $producto->reservarStock($cantidad);
                }
            }

            $existe = FichaProducto::where('id_ficha', $ficha->uuid)->where('id_producto', $producto->uuid)->first();
        if ($existe) {
            $existe->cantidad += $cantidad;
            $existe->precio += ($producto->precio * $cantidad);
            $existe->save();
        } else {
            $fichaProducto = FichaProducto::create([
                'uuid' => (string) Uuid::uuid4(),
                'id_ficha' => $ficha->uuid,
                'id_producto' => $producto->uuid,
                'precio' => $producto->precio * $cantidad,
                'cantidad' => $cantidad
            ]);
        }
        return redirect()->route('fichas.productos', [
            'uuid' => $ficha,
            'uuid2' => $familia
        ])->with('success', $cantidad . 'x ' . $producto->nombre . ' ' . __('añadido a la ficha'));
        });
    }

    public function lista($uuid)
    {
        $ficha = Ficha::find($uuid);
        $ficha->precio = $this->ObtenerImporteFicha($ficha);
        $ajustes = app('ajustes'); // Usar cache en lugar de query
        
        if($ajustes->modo_operacion == 'mesas'){
            $productosFicha = FichaProducto::with('producto:uuid,nombre,precio,imagen,combinado,iva')
                ->where('id_ficha', $uuid)
                ->get();
        }else{
            $productosFicha = FichaProducto::with('producto:uuid,nombre,precio,imagen,combinado,iva')
                ->where('id_ficha', $uuid)
                ->groupBy('id_producto')
                ->selectRaw('id_producto, sum(cantidad) as cantidad, sum(precio) as precio')
                ->get();
        }
        
        foreach ($productosFicha as $productoFicha) {
            $productoFicha->borrable = true;
        }

        return view('fichas.lista', compact('ficha', 'productosFicha', 'ajustes'));
    }

    public function destroylista(string $uuid, string $uuid2)
    {
        return DB::transaction(function () use ($uuid, $uuid2) {
            $ficha = Ficha::find($uuid);
            
            // Verificar si uuid2 es un UUID de ficha_producto (modo mesas) o un id_producto (modo fichas)
            $fichaProducto = FichaProducto::where('uuid', $uuid2)->first();
            
            if ($fichaProducto && $fichaProducto->id_ficha === $uuid) {
                // Es un UUID de ficha_producto (modo mesas) - borrar solo ese registro
                // Liberar stock reservado (solo si ficha abierta)
                if ($ficha->estado == 0) {
                    $producto = Producto::with('composicion.componenteProducto')->find($fichaProducto->id_producto);
                    if ($producto) {
                        if ($producto->combinado == 1) {
                            // Liberar stock de componentes
                            foreach ($producto->composicion as $composicion) {
                                $componente = $composicion->componenteProducto;
                                if ($componente) {
                                    $componente->liberarStock($fichaProducto->cantidad);
                                }
                            }
                        } else {
                            $producto->liberarStock($fichaProducto->cantidad);
                        }
                    }
                }
                $fichaProducto->delete();
            } else {
                // Es un id_producto (modo fichas) - borrar todos los registros con ese producto
                $fichaProductos = FichaProducto::where('id_ficha', $uuid)->where('id_producto', $uuid2)->get();
                $totalCantidad = $fichaProductos->sum('cantidad');
                
                // Liberar stock reservado (solo si ficha abierta)
                if ($ficha->estado == 0 && $totalCantidad > 0) {
                    $producto = Producto::with('composicion.componenteProducto')->find($uuid2);
                    if ($producto) {
                        if ($producto->combinado == 1) {
                            // Liberar stock de componentes
                            foreach ($producto->composicion as $composicion) {
                                $componente = $composicion->componenteProducto;
                                if ($componente) {
                                    $componente->liberarStock($totalCantidad);
                                }
                            }
                        } else {
                            $producto->liberarStock($totalCantidad);
                        }
                    }
                }
                
                foreach ($fichaProductos as $fichaProducto) {
                    $fichaProducto->delete();
                }
            }
            
            return redirect()->route('fichas.lista', $uuid)
                ->with('success', __('Producto eliminado de la ficha'));
        });
    }

    public function updatelista(string $uuid, string $uuid2, int $cantidad)
    {
        return DB::transaction(function () use ($uuid, $uuid2, $cantidad) {
            $ficha = Ficha::find($uuid);
            $producto = Producto::with('composicion.componenteProducto')->find($uuid2);
            
            // Si estamos añadiendo cantidad y la ficha está abierta, verificar stock
            if ($cantidad > 0 && $ficha->estado == 0) {
                if ($producto->combinado == 1) {
                    foreach ($producto->composicion as $composicion) {
                        $componente = $composicion->componenteProducto;
                        if ($componente && !$componente->tieneStockDisponible($cantidad)) {
                            return redirect()->route('fichas.lista', $uuid)
                                ->with('error', "Stock insuficiente de {$componente->nombre}. Disponible: {$componente->stock_disponible}");
                        }
                    }
                } else {
                    if (!$producto->tieneStockDisponible($cantidad)) {
                        return redirect()->route('fichas.lista', $uuid)
                            ->with('error', "Stock insuficiente de {$producto->nombre}. Disponible: {$producto->stock_disponible}");
                    }
                }
            }
            
            //Buscar el total del producto de la ficha en FichaProducto
            //Si la cantidad es positiva insertar un elemento en FichaProducto
            //Si la cantidad es negativa eliminar un elemento en FichaProducto
            $total = FichaProducto::where('id_ficha', $uuid)->where('id_producto', $uuid2)->sum('cantidad');
            
            if ($producto->combinado == 1) {
                $precio = 0;
                foreach ($producto->composicion as $composicion) {
                    $componente = $composicion->componenteProducto;
                    $precio += $componente->precio;
                }
                $producto->precio = $precio;
            }
            
            if ($cantidad > 0) {
                // Reservar stock (solo si ficha abierta)
                if ($ficha->estado == 0) {
                    if ($producto->combinado == 1) {
                        foreach ($producto->composicion as $composicion) {
                            $componente = $composicion->componenteProducto;
                            if ($componente) {
                                $componente->reservarStock($cantidad);
                            }
                        }
                    } else {
                        $producto->reservarStock($cantidad);
                    }
                }
                
                for ($cantidad; $cantidad > 0; $cantidad--) {
                    $fichaProducto = FichaProducto::create([
                        'uuid' => (string) Uuid::uuid4(),
                        'id_ficha' => $uuid,
                        'id_producto' => $uuid2,
                        'precio' => $producto->precio,
                        'cantidad' => 1
                    ]);
                }
                return redirect()->route('fichas.lista', $uuid)
                    ->with('success', __('Producto añadido a la ficha'));
            } else {
                // Liberar stock reservado (solo si ficha abierta)
                if ($ficha->estado == 0) {
                    $cantidadLiberar = abs($cantidad);
                    if ($producto->combinado == 1) {
                        foreach ($producto->composicion as $composicion) {
                            $componente = $composicion->componenteProducto;
                            if ($componente) {
                                $componente->liberarStock($cantidadLiberar);
                            }
                        }
                    } else {
                        $producto->liberarStock($cantidadLiberar);
                    }
                }
                
                $fichaProductos = FichaProducto::where('id_ficha', $uuid)->where('id_producto', $uuid2)->take(abs($cantidad))->get();
                foreach ($fichaProductos as $fichaProducto) {
                    $fichaProducto->delete();
                }
                return redirect()->route('fichas.lista', $uuid)
                    ->with('success', __('Producto eliminado de la ficha'));
            }
        });
    }

    // ========== MÉTODOS PARA SISTEMA DE MESAS ==========
    
    /**
     * Mostrar grid de mesas (modo restaurante)
     */
    public function indexMesas()
    {
        $user = Auth::user();
        if ($user && $user->role_id == \App\Enums\Role::COCINERO) {
            return redirect()->route('cocina.mesas');
        }
        $ajustes = DB::connection('site')->table('ajustes')->first();

        // TODOS los camareros ven TODAS las mesas
        $mesas = Ficha::mesas()
            ->with(['camarero', 'productos.producto', 'servicios.servicio'])
            ->orderBy('orden', 'asc')
            ->orderByRaw('CAST(numero_mesa AS UNSIGNED) ASC')
            ->get();

        // Calcular importe y si tiene productos preparados para cada mesa
        $mesas->each(function($mesa) {
            $totalProductos = $mesa->productos->sum(function($fp) {
                return $fp->producto ? $fp->producto->precio : 0;
            });
            $totalServicios = $mesa->servicios->sum(function($fs) {
                return $fs->servicio ? $fs->servicio->precio : 0;
            });
            $mesa->importe = $totalProductos + $totalServicios;
            // ¿Tiene algún producto preparado?
            $mesa->tiene_preparado = $mesa->productos->contains(function($fp) {
                return $fp->estado === 'preparado';
            });
        });

        // Estadísticas personales del camarero
        $misMesas = $mesas->where('camarero_id', $user->id)->where('estado_mesa', 'ocupada');
        $estadisticas = [
            'libres' => $mesas->where('estado_mesa', 'libre')->count(),
            'ocupadas' => $mesas->where('estado_mesa', 'ocupada')->count(),
            'mis_mesas' => $misMesas->count(),
            'mi_facturacion' => $misMesas->sum('importe')
        ];

        return view('fichas.mesas-grid', compact('mesas', 'estadisticas', 'ajustes'));
    }
    
    /**
     * Abrir una mesa nueva
     */
    public function abrirMesa(Request $request, $mesaId)
    {
        $request->validate([
            'numero_comensales' => 'required|integer|min:1|max:20'
        ]);
        
        try {
            return DB::transaction(function () use ($request, $mesaId) {
                // Locking pesimista: bloquea el registro hasta que termine la transacción
                $mesa = Ficha::where('uuid', $mesaId)
                    ->lockForUpdate()
                    ->firstOrFail();
                
                // Verificar que esté libre
                if ($mesa->estado_mesa != 'libre') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Esta mesa ya está ocupada'
                    ], 400);
                }
                
                // Abrir mesa y asignar al camarero actual
                $mesa->update([
                    'estado_mesa' => 'ocupada',
                    'camarero_id' => Auth::id(),
                    'numero_comensales' => $request->numero_comensales,
                    'hora_apertura' => now(),
                    'observaciones' => $request->notas ?? ''
                ]);
                
                // Registrar en historial
                \App\Models\MesaHistorial::create([
                    'mesa_id' => $mesa->uuid,
                    'accion' => 'abrir',
                    'camarero_id' => Auth::id(),
                    'detalles' => json_encode([
                        'comensales' => $request->numero_comensales,
                        'notas' => $request->notas
                    ])
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Mesa abierta correctamente'
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al abrir la mesa. Por favor, inténtalo de nuevo.'
            ], 500);
        }
    }
    
    /**
     * Tomar mesa de otro camarero
     */
    public function tomarMesa($mesaId)
    {
        try {
            return DB::transaction(function () use ($mesaId) {
                // Locking pesimista: bloquea el registro hasta que termine la transacción
                $mesa = Ficha::where('uuid', $mesaId)
                    ->lockForUpdate()
                    ->firstOrFail();
                
                // Verificar que esté ocupada (no libre ni cerrada)
                if ($mesa->estado_mesa != 'ocupada') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Esta mesa no está disponible para tomar'
                    ], 400);
                }
                
                // Verificar que no sea ya del camarero actual
                if ($mesa->camarero_id == Auth::id()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Esta mesa ya es tuya'
                    ], 400);
                }
                
                $camareroAnterior = $mesa->camarero_id;
                
                // Transferir mesa al camarero actual
                $mesa->update([
                    'ultimo_camarero_id' => $camareroAnterior,
                    'camarero_id' => Auth::id()
                ]);
                
                // Registrar en historial
                \App\Models\MesaHistorial::create([
                    'mesa_id' => $mesa->uuid,
                    'accion' => 'tomar',
                    'camarero_id' => Auth::id(),
                    'camarero_anterior_id' => $camareroAnterior,
                    'detalles' => json_encode([
                        'importe_actual' => $mesa->importe
                    ])
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Mesa tomada correctamente'
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al tomar la mesa. Por favor, inténtalo de nuevo.'
            ], 500);
        }
    }
    
    /**
     * Obtener resumen de una mesa para el modal de cierre
     */
    public function resumenMesa($mesaId)
    {
        $mesa = Ficha::with(['camarero', 'productos.producto', 'servicios.servicio'])
            ->findOrFail($mesaId);
        
        $productos = $mesa->productos->map(function($fp) {
            return [
                'cantidad' => 1,
                'nombre' => $fp->producto->nombre,
                'precio' => $fp->producto->precio,
                'precio_total' => number_format($fp->producto->precio, 2) . ' €'
            ];
        })->groupBy('nombre')->map(function($group) {
            $first = $group->first();
            return [
                'cantidad' => $group->count(),
                'nombre' => $first['nombre'],
                'precio_total' => number_format($first['precio'] * $group->count(), 2) . ' €'
            ];
        })->values();
        
        $servicios = $mesa->servicios->map(function($fs) {
            return [
                'nombre' => $fs->servicio->nombre,
                'precio' => number_format($fs->servicio->precio, 2) . ' €'
            ];
        });
        
        // Calcular importe total
        $totalProductos = $mesa->productos->sum(function($fp) {
            return $fp->producto ? $fp->producto->precio : 0;
        });
        $totalServicios = $mesa->servicios->sum(function($fs) {
            return $fs->servicio ? $fs->servicio->precio : 0;
        });
        $importeTotal = $totalProductos + $totalServicios;
        
        return response()->json([
            'numero_mesa' => $mesa->numero_mesa,
            'numero_comensales' => $mesa->numero_comensales,
            'camarero' => ($mesa->camarero && $mesa->camarero->name) ? $mesa->camarero->name : 'N/A',
            'hora_apertura' => $mesa->hora_apertura ? $mesa->hora_apertura->format('H:i') : 'N/A',
            'importe_formateado' => number_format($importeTotal, 2) . ' €',
            'productos' => $productos,
            'servicios' => $servicios
        ]);
    }
    
    /**
     * Cerrar mesa y procesar pago
     */
    public function cerrarMesa(Request $request, $mesaId)
    {
        $request->validate([
            'metodo_pago' => 'required|in:efectivo,tarjeta,mixto',
            'propina' => 'nullable|numeric|min:0'
        ]);
        
        try {
            return DB::transaction(function () use ($request, $mesaId) {
                // Locking pesimista: bloquea la mesa y productos relacionados
                $mesa = Ficha::where('uuid', $mesaId)
                    ->with(['productos.producto', 'servicios.servicio'])
                    ->lockForUpdate()
                    ->firstOrFail();
                
                // Verificar que sea el camarero asignado o admin
                if ($mesa->camarero_id != Auth::id() && (!Auth::check() || Auth::user()->role_id != 1)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tienes permiso para cerrar esta mesa'
                    ], 403);
                }
                
                // Verificar que esté en estado ocupada
                if ($mesa->estado_mesa != 'ocupada') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Esta mesa no está en estado ocupada'
                    ], 400);
                }
        
        // Calcular importe total de la mesa con IVA
        $subtotal = 0;
        $totalIva = 0;
        $ivaDesglose = [];
        
        // Calcular productos con IVA (el precio ya incluye IVA)
        foreach ($mesa->productos as $fp) {
            if ($fp->producto) {
                $iva = $fp->producto->iva ?? 0;
                $pvp = $fp->producto->precio * $fp->cantidad; // PVP con IVA incluido
                $baseImponible = $pvp / (1 + $iva / 100);
                $importeIva = $pvp - $baseImponible;
                
                $subtotal += $baseImponible;
                $totalIva += $importeIva;
                
                $ivaKey = number_format($iva, 2);
                if (!isset($ivaDesglose[$ivaKey])) {
                    $ivaDesglose[$ivaKey] = ['base' => 0, 'cuota' => 0];
                }
                $ivaDesglose[$ivaKey]['base'] += $baseImponible;
                $ivaDesglose[$ivaKey]['cuota'] += $importeIva;
            }
        }
        
        // Calcular servicios con IVA (el precio ya incluye IVA)
        foreach ($mesa->servicios as $fs) {
            if ($fs->servicio) {
                $iva = $fs->servicio->iva ?? 0;
                $pvp = $fs->servicio->precio * $fs->cantidad; // PVP con IVA incluido
                $baseImponible = $pvp / (1 + $iva / 100);
                $importeIva = $pvp - $baseImponible;
                
                $subtotal += $baseImponible;
                $totalIva += $importeIva;
                
                $ivaKey = number_format($iva, 2);
                if (!isset($ivaDesglose[$ivaKey])) {
                    $ivaDesglose[$ivaKey] = ['base' => 0, 'cuota' => 0];
                }
                $ivaDesglose[$ivaKey]['base'] += $baseImponible;
                $ivaDesglose[$ivaKey]['cuota'] += $importeIva;
            }
        }
        
        $importeTotal = $subtotal + $totalIva;
        $propina = $request->propina ?? 0;
        
        // Crear FichaRecibo con el importe total (marcado como pagado)
        FichaRecibo::create([
            'uuid' => (string) Uuid::uuid4(),
            'id_ficha' => $mesa->uuid,
            'user_id' => $mesa->camarero_id, // Asociado al camarero de la mesa
            'tipo' => 1, // Tipo 1 = ingreso/venta
            'estado' => 1, // Estado 1 = pagado
            'precio' => $importeTotal + $propina,
            'fecha' => now()
        ]);
        
                // OPTIMIZADO: Confirmar ventas con eager loading de composición
                $stockService = new \App\Services\StockNotificationService();
                
                foreach ($mesa->productos as $fichaProducto) {
                    // Lock del producto para evitar race conditions en stock
                    $producto = Producto::with(['composicion.componenteProducto' => function($query) {
                        $query->select('uuid', 'nombre', 'stock', 'stock_reservado');
                    }])
                    ->where('uuid', $fichaProducto->id_producto)
                    ->lockForUpdate()
                    ->first();
                    
                    if ($producto) {
                        if ($producto->combinado == 1) {
                            // Producto combinado: confirmar venta de componentes (ya cargados con eager loading)
                            foreach ($producto->composicion as $composicion) {
                                $componente = $composicion->componenteProducto;
                                
                                if ($componente) {
                                    // Lock del componente
                                    $componenteLocked = Producto::where('uuid', $componente->uuid)
                                        ->lockForUpdate()
                                        ->first();
                                    
                                    if ($componenteLocked) {
                                        // Verificar que haya stock suficiente
                                        if ($componenteLocked->stock < $fichaProducto->cantidad) {
                                            throw new \Exception('Stock insuficiente para ' . $componenteLocked->nombre);
                                        }
                                        
                                        // Confirmar venta: descuenta stock real y libera reserva
                                        $componenteLocked->confirmarVenta($fichaProducto->cantidad);
                                        
                                        // OPTIMIZADO: Verificar stock bajo de forma asíncrona
                                        NotificarStockBajo::dispatch($componenteLocked->uuid)->afterCommit();
                                    }
                                }
                            }
                        } else {
                            // Producto simple: verificar y confirmar venta
                            if ($producto->stock < $fichaProducto->cantidad) {
                                throw new \Exception('Stock insuficiente para ' . $producto->nombre);
                            }
                            
                            // Confirmar venta: descuenta stock real y libera reserva
                            $producto->confirmarVenta($fichaProducto->cantidad);
                            
                            // OPTIMIZADO: Verificar stock bajo de forma asíncrona
                            NotificarStockBajo::dispatch($producto->uuid)->afterCommit();
                        }
                    }
                }
                
                // Cerrar mesa
                $mesa->update([
                    'estado_mesa' => 'cerrada',
                    'hora_cierre' => now(),
                    'ultimo_camarero_id' => Auth::id(),
                    'estado' => 1,
                    'precio' => $importeTotal + $propina
                ]);
                
                // Registrar en historial con desglose de IVA
                \App\Models\MesaHistorial::create([
                    'mesa_id' => $mesa->uuid,
                    'accion' => 'cerrar',
                    'camarero_id' => Auth::id(),
                    'detalles' => [
                        'metodo_pago' => $request->metodo_pago,
                        'propina' => $propina,
                        'subtotal' => round($subtotal, 2),
                        'iva_desglose' => $ivaDesglose,
                        'total_iva' => round($totalIva, 2),
                        'importe_total' => round($importeTotal + $propina, 2)
                    ]
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Mesa cerrada correctamente',
                    'desglose' => [
                        'subtotal' => round($subtotal, 2),
                        'iva_desglose' => $ivaDesglose,
                        'total_iva' => round($totalIva, 2),
                        'propina' => $propina,
                        'total' => round($importeTotal + $propina, 2)
                    ]
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Error al cerrar mesa: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Error al cerrar la mesa. Por favor, inténtalo de nuevo.'
            ], 500);
        }
    }
    
    /**
     * Liberar mesa cerrada para volver a usarla
     */
    public function liberarMesa($mesaId)
    {
        try {
            return DB::transaction(function () use ($mesaId) {
                // Locking pesimista
                $mesa = Ficha::where('uuid', $mesaId)
                    ->lockForUpdate()
                    ->firstOrFail();
                
                // Solo admin o el mismo camarero puede liberar
                if ($mesa->camarero_id != Auth::id() && (!Auth::check() || Auth::user()->role_id != 1)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tienes permiso'
                    ], 403);
                }
                
                // Verificar que esté cerrada
                if ($mesa->estado_mesa != 'cerrada') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Esta mesa no está cerrada'
                    ], 400);
                }
        
        // Guardar productos y servicios antes de eliminar (para historial de ventas)
        $productos = FichaProducto::where('id_ficha', $mesa->uuid)
            ->with('producto')
            ->get()
            ->map(function($fp) {
                $iva = $fp->producto ? ($fp->producto->iva ?? 0) : 0;
                $baseImponible = $fp->cantidad * $fp->precio;
                $importeIva = $baseImponible * ($iva / 100);
                
                return [
                    'producto_id' => $fp->id_producto,
                    'nombre' => $fp->producto ? $fp->producto->nombre : 'Producto eliminado',
                    'cantidad' => $fp->cantidad,
                    'precio' => $fp->precio,
                    'iva' => $iva,
                    'base_imponible' => $baseImponible,
                    'importe_iva' => $importeIva,
                    'total' => $baseImponible + $importeIva
                ];
            });
            
        $servicios = FichaServicio::where('id_ficha', $mesa->uuid)
            ->with('servicio')
            ->get()
            ->map(function($fs) {
                $iva = $fs->servicio ? ($fs->servicio->iva ?? 0) : 0;
                $baseImponible = $fs->cantidad * $fs->precio;
                $importeIva = $baseImponible * ($iva / 100);
                
                return [
                    'servicio_id' => $fs->id_servicio,
                    'nombre' => $fs->servicio ? $fs->servicio->nombre : 'Servicio eliminado',
                    'cantidad' => $fs->cantidad,
                    'precio' => $fs->precio,
                    'iva' => $iva,
                    'base_imponible' => $baseImponible,
                    'importe_iva' => $importeIva,
                    'total' => $baseImponible + $importeIva
                ];
            });
        
        // Calcular totales generales
        $subtotal = $productos->sum('base_imponible') + $servicios->sum('base_imponible');
        $totalIva = $productos->sum('importe_iva') + $servicios->sum('importe_iva');
        $totalGeneral = $subtotal + $totalIva;
        
        // Calcular desglose de IVA por tipo
        $ivaDesglose = [];
        foreach ($productos->concat($servicios) as $item) {
            $ivaKey = number_format($item['iva'], 2);
            if (!isset($ivaDesglose[$ivaKey])) {
                $ivaDesglose[$ivaKey] = [
                    'base' => 0,
                    'cuota' => 0
                ];
            }
            $ivaDesglose[$ivaKey]['base'] += $item['base_imponible'];
            $ivaDesglose[$ivaKey]['cuota'] += $item['importe_iva'];
        }
        
        // Resetear mesa a estado libre
        $mesa->update([
            'estado_mesa' => 'libre',
            'camarero_id' => null,
            'ultimo_camarero_id' => $mesa->camarero_id,
            'numero_comensales' => 0,
            'hora_apertura' => null,
            'hora_cierre' => null,
            'estado' => 0
        ]);
        
                // Limpiar consumos de la mesa
                FichaProducto::where('id_ficha', $mesa->uuid)->delete();
                FichaServicio::where('id_ficha', $mesa->uuid)->delete();
                
                // Guardar en historial con los productos y servicios
                \App\Models\MesaHistorial::create([
                    'mesa_id' => $mesa->uuid,
                    'accion' => 'liberar',
                    'camarero_id' => Auth::id(),
                    'detalles' => [
                        'productos' => $productos,
                        'servicios' => $servicios,
                        'subtotal' => round($subtotal, 2),
                        'iva_desglose' => $ivaDesglose,
                        'total_iva' => round($totalIva, 2),
                        'total_general' => round($totalGeneral, 2)
                    ]
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Mesa liberada'
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al liberar la mesa. Por favor, inténtalo de nuevo.'
            ], 500);
        }
    }

    /**
     * Generar ticket de una mesa cerrada para imprimir (genera PDF en línea)
     */
    public function generarTicket($mesaId)
    {
        $ficha = Ficha::with(['productos.producto', 'servicios.servicio', 'camarero', 'gastos'])
            ->findOrFail($mesaId);
        
        // Verificar que la mesa/ficha esté cerrada (modo mesas o modo fichas)
        $ajustes = \App\Models\Ajustes::first();
        $esModoMesas = isset($ajustes->modo_operacion) && $ajustes->modo_operacion === 'mesas';
        
        if ($esModoMesas && $ficha->estado_mesa !== 'cerrada') {
            return redirect()->back()->with('error', 'La mesa debe estar cerrada para imprimir el ticket');
        } elseif (!$esModoMesas && $ficha->estado != 1) {
            return redirect()->back()->with('error', 'La ficha debe estar cerrada para imprimir el ticket');
        }
        
        // Generar nombre del archivo
        $nombreArchivo = $esModoMesas 
            ? 'ticket_mesa_' . ($ficha->numero_mesa ?? $ficha->uuid) . '_' . date('Ymd') . '.pdf'
            : 'ticket_ficha_' . $ficha->uuid . '_' . date('Ymd') . '.pdf';
        
        $rutaTickets = public_path('tickets');
        
        // Crear directorio si no existe
        if (!file_exists($rutaTickets)) {
            mkdir($rutaTickets, 0755, true);
        }
        
        $rutaCompleta = $rutaTickets . '/' . $nombreArchivo;
        
        // Si el archivo ya existe, redirigir directamente a él
        if (file_exists($rutaCompleta)) {
            return redirect(asset('tickets/' . $nombreArchivo));
        }
        
        // Calcular totales con IVA
        $lineas = [];
        $subtotal = 0;
        $totalIva = 0;
        $ivaDesglose = [];
        
        // Añadir productos
        foreach ($ficha->productos as $fp) {
            if ($fp->producto) {
                $iva = $fp->producto->iva ?? 21;
                $pvp = $fp->precio; // El precio ya está multiplicado por la cantidad en FichaProducto
                $precioUnitario = $fp->cantidad > 0 ? $fp->precio / $fp->cantidad : $fp->precio;
                $baseImponible = $pvp / (1 + $iva / 100);
                $importeIva = $pvp - $baseImponible;
                
                $lineas[] = [
                    'tipo' => 'producto',
                    'nombre' => $fp->producto->nombre,
                    'cantidad' => $fp->cantidad,
                    'precio_unitario' => $precioUnitario,
                    'iva' => $iva,
                    'total' => $pvp
                ];
                
                $subtotal += $baseImponible;
                $totalIva += $importeIva;
                
                // Agrupar por IVA
                $ivaKey = number_format($iva, 2);
                if (!isset($ivaDesglose[$ivaKey])) {
                    $ivaDesglose[$ivaKey] = [
                        'porcentaje' => $iva,
                        'base' => 0,
                        'cuota' => 0
                    ];
                }
                $ivaDesglose[$ivaKey]['base'] += $baseImponible;
                $ivaDesglose[$ivaKey]['cuota'] += $importeIva;
            }
        }
        
        // Añadir servicios
        foreach ($ficha->servicios as $fs) {
            if ($fs->servicio) {
                $iva = $fs->servicio->iva ?? 21;
                $pvp = $fs->precio; // El precio ya incluye IVA
                $baseImponible = $pvp / (1 + $iva / 100);
                $importeIva = $pvp - $baseImponible;
                
                $lineas[] = [
                    'tipo' => 'servicio',
                    'nombre' => $fs->servicio->nombre,
                    'cantidad' => 1,
                    'precio_unitario' => $fs->precio,
                    'iva' => $iva,
                    'total' => $pvp
                ];
                
                $subtotal += $baseImponible;
                $totalIva += $importeIva;
                
                // Agrupar por IVA
                $ivaKey = number_format($iva, 2);
                if (!isset($ivaDesglose[$ivaKey])) {
                    $ivaDesglose[$ivaKey] = [
                        'porcentaje' => $iva,
                        'base' => 0,
                        'cuota' => 0
                    ];
                }
                $ivaDesglose[$ivaKey]['base'] += $baseImponible;
                $ivaDesglose[$ivaKey]['cuota'] += $importeIva;
            }
        }
        
        // Añadir gastos
        foreach ($ficha->gastos as $fg) {
            $iva = 21; // IVA por defecto para gastos
            $pvp = $fg->precio;
            $baseImponible = $pvp / (1 + $iva / 100);
            $importeIva = $pvp - $baseImponible;
            
            $lineas[] = [
                'tipo' => 'gasto',
                'nombre' => $fg->descripcion ?? 'Gasto',
                'cantidad' => 1,
                'precio_unitario' => $fg->precio,
                'iva' => $iva,
                'total' => $pvp
            ];
            
            $subtotal += $baseImponible;
            $totalIva += $importeIva;
            
            // Agrupar por IVA
            $ivaKey = number_format($iva, 2);
            if (!isset($ivaDesglose[$ivaKey])) {
                $ivaDesglose[$ivaKey] = [
                    'porcentaje' => $iva,
                    'base' => 0,
                    'cuota' => 0
                ];
            }
            $ivaDesglose[$ivaKey]['base'] += $baseImponible;
            $ivaDesglose[$ivaKey]['cuota'] += $importeIva;
        }
        
        $total = $subtotal + $totalIva;
        $site = app('site');
        
        // Generar PDF usando dompdf
        $pdf = PDF::loadView('fichas.ticket-pdf', compact('ficha', 'lineas', 'subtotal', 'totalIva', 'total', 'ivaDesglose', 'ajustes', 'site'));
        
        // Configurar ancho de ticket (80mm = 226.77 puntos)
        $pdf->setPaper([0, 0, 226.77, 841.89], 'portrait');
        
        // Configurar opciones de dompdf
        $pdf->getDomPDF()->set_option('isPhpEnabled', true);
        
        // Guardar el PDF en el servidor
        $pdf->save($rutaCompleta);
        
        // Redirigir a la URL del PDF
        return redirect(asset('tickets/' . $nombreArchivo));
    }

    /**
     * Descargar ticket como PDF
     */
    public function descargarTicket($fichaId)
    {
        $ficha = Ficha::with(['productos.producto', 'servicios.servicio', 'camarero', 'usuarios', 'gastos'])
            ->findOrFail($fichaId);
        
        // Verificar que la ficha esté cerrada
        $ajustes = \App\Models\Ajustes::first();
        $esModoMesas = isset($ajustes->modo_operacion) && $ajustes->modo_operacion === 'mesas';
        
        if ($esModoMesas && $ficha->estado_mesa !== 'cerrada') {
            return redirect()->back()->with('error', 'La mesa debe estar cerrada para descargar el ticket');
        } elseif (!$esModoMesas && $ficha->estado != 1) {
            return redirect()->back()->with('error', 'La ficha debe estar cerrada para descargar el ticket');
        }
        
        // Generar nombre del archivo
        $nombreArchivo = $esModoMesas 
            ? 'ticket_mesa_' . ($ficha->numero_mesa ?? $ficha->uuid) . '_' . date('Ymd') . '.pdf'
            : 'ticket_ficha_' . $ficha->uuid . '_' . date('Ymd') . '.pdf';
        
        $rutaTickets = public_path('tickets');
        
        // Crear directorio si no existe
        if (!file_exists($rutaTickets)) {
            mkdir($rutaTickets, 0755, true);
        }
        
        $rutaCompleta = $rutaTickets . '/' . $nombreArchivo;
        
        // Si el archivo ya existe, redirigir directamente a él
        if (file_exists($rutaCompleta)) {
            return redirect(asset('tickets/' . $nombreArchivo));
        }
        
        // Calcular totales con IVA
        $lineas = [];
        $subtotal = 0;
        $totalIva = 0;
        $ivaDesglose = [];
        
        // Añadir productos
        foreach ($ficha->productos as $fp) {
            if ($fp->producto) {
                $iva = $fp->producto->iva ?? 21;
                $pvp = $fp->precio; // El precio ya está multiplicado por la cantidad en FichaProducto
                $precioUnitario = $fp->cantidad > 0 ? $fp->precio / $fp->cantidad : $fp->precio;
                $baseImponible = $pvp / (1 + $iva / 100);
                $importeIva = $pvp - $baseImponible;
                
                $lineas[] = [
                    'tipo' => 'producto',
                    'nombre' => $fp->producto->nombre,
                    'cantidad' => $fp->cantidad,
                    'precio_unitario' => $precioUnitario,
                    'iva' => $iva,
                    'total' => $pvp
                ];
                
                $subtotal += $baseImponible;
                $totalIva += $importeIva;
                
                $ivaKey = number_format($iva, 2);
                if (!isset($ivaDesglose[$ivaKey])) {
                    $ivaDesglose[$ivaKey] = [
                        'porcentaje' => $iva,
                        'base' => 0,
                        'cuota' => 0
                    ];
                }
                $ivaDesglose[$ivaKey]['base'] += $baseImponible;
                $ivaDesglose[$ivaKey]['cuota'] += $importeIva;
            }
        }
        
        // Añadir servicios
        foreach ($ficha->servicios as $fs) {
            if ($fs->servicio) {
                $iva = $fs->servicio->iva ?? 21;
                $pvp = $fs->precio;
                $baseImponible = $pvp / (1 + $iva / 100);
                $importeIva = $pvp - $baseImponible;
                
                $lineas[] = [
                    'tipo' => 'servicio',
                    'nombre' => $fs->servicio->nombre,
                    'cantidad' => 1,
                    'precio_unitario' => $fs->precio,
                    'iva' => $iva,
                    'total' => $pvp
                ];
                
                $subtotal += $baseImponible;
                $totalIva += $importeIva;
                
                $ivaKey = number_format($iva, 2);
                if (!isset($ivaDesglose[$ivaKey])) {
                    $ivaDesglose[$ivaKey] = [
                        'porcentaje' => $iva,
                        'base' => 0,
                        'cuota' => 0
                    ];
                }
                $ivaDesglose[$ivaKey]['base'] += $baseImponible;
                $ivaDesglose[$ivaKey]['cuota'] += $importeIva;
            }
        }
        
        // Añadir gastos
        foreach ($ficha->gastos as $fg) {
            $iva = 21; // IVA por defecto para gastos
            $pvp = $fg->precio;
            $baseImponible = $pvp / (1 + $iva / 100);
            $importeIva = $pvp - $baseImponible;
            
            $lineas[] = [
                'tipo' => 'gasto',
                'nombre' => $fg->descripcion ?? 'Gasto',
                'cantidad' => 1,
                'precio_unitario' => $fg->precio,
                'iva' => $iva,
                'total' => $pvp
            ];
            
            $subtotal += $baseImponible;
            $totalIva += $importeIva;
            
            $ivaKey = number_format($iva, 2);
            if (!isset($ivaDesglose[$ivaKey])) {
                $ivaDesglose[$ivaKey] = [
                    'porcentaje' => $iva,
                    'base' => 0,
                    'cuota' => 0
                ];
            }
            $ivaDesglose[$ivaKey]['base'] += $baseImponible;
            $ivaDesglose[$ivaKey]['cuota'] += $importeIva;
        }
        
        $total = $subtotal + $totalIva;
        $site = app('site');
        
        // Generar PDF usando dompdf
        $pdf = PDF::loadView('fichas.ticket-pdf', compact('ficha', 'lineas', 'subtotal', 'totalIva', 'total', 'ivaDesglose', 'ajustes', 'site'));
        
        // Configurar ancho de ticket (80mm = 226.77 puntos)
        $pdf->setPaper([0, 0, 226.77, 841.89], 'portrait');
        
        // Configurar opciones de dompdf
        $pdf->getDomPDF()->set_option('isPhpEnabled', true);
        
        // Guardar el PDF en el servidor
        $pdf->save($rutaCompleta);
        
        // Redirigir a la URL del PDF
        return redirect(asset('tickets/' . $nombreArchivo));
    }

    /**
     * Generar múltiples mesas automáticamente (solo usuarios tipo < 4)
     */
    public function generarMesas(Request $request)
    {
        // Verificar que el usuario tenga permisos (tipo < 4, es decir, no camareros)
        if (!Auth::check() || Auth::user()->role_id >= 4) {
            return redirect()->back()->with('error', __('No tienes permisos para crear mesas'));
        }

        $request->validate([
            'cantidad' => 'required|integer|min:1|max:100',
            'prefijo' => 'required|string|max:20'
        ]);

        $cantidad = $request->cantidad;
        $prefijo = $request->prefijo;
        
        $mesasCreadas = 0;

        try {
            DB::beginTransaction();

            for ($i = 1; $i <= $cantidad; $i++) {
                $uuid = (string) Uuid::uuid4();
                $descripcion = $prefijo . $i;

                Ficha::create([
                    'uuid' => $uuid,
                    'descripcion' => $descripcion,
                    'user_id' => Auth::id(),
                    'precio' => 0,
                    'invitados_grupo' => 0,
                    'estado' => 0,
                    'tipo' => 5, // Tipo 5 = Mesa
                    'fecha' => Carbon::now()->format('Y-m-d'),
                    'hora' => null,
                    'menu' => null,
                    'responsables' => null,
                    'modo' => 'mesa',
                    'numero_mesa' => $i,
                    'estado_mesa' => 'libre',
                    'camarero_id' => null,
                    'numero_comensales' => 0,
                    'hora_apertura' => null,
                    'hora_cierre' => null
                ]);

                $mesasCreadas++;
            }

            DB::commit();

            return redirect()->back()->with('success', __('Se han creado :cantidad mesas correctamente', ['cantidad' => $mesasCreadas]));

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', __('Error al crear las mesas: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Crear una mesa individual
     */
    public function crearMesaIndividual(Request $request)
    {
        // Verificar permisos
        if (!Auth::check() || Auth::user()->role_id >= 4) {
            return redirect()->back()->with('error', __('No tienes permisos para crear mesas'));
        }

        $request->validate([
            'descripcion' => 'required|string|max:100',
            'numero_mesa' => 'required|integer|min:1|max:999'
        ]);

        try {
            $uuid = (string) Uuid::uuid4();

            Ficha::create([
                'uuid' => $uuid,
                'descripcion' => $request->descripcion,
                'user_id' => Auth::id(),
                'precio' => 0,
                'invitados_grupo' => 0,
                'estado' => 0,
                'tipo' => 5,
                'fecha' => Carbon::now()->format('Y-m-d'),
                'hora' => null,
                'menu' => null,
                'responsables' => null,
                'modo' => 'mesa',
                'numero_mesa' => $request->numero_mesa,
                'estado_mesa' => 'libre',
                'camarero_id' => null,
                'numero_comensales' => 0,
                'hora_apertura' => null,
                'hora_cierre' => null
            ]);

            return redirect()->back()->with('success', __('Mesa creada correctamente'));

        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Error al crear la mesa: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Actualizar una mesa existente
     */
    public function actualizarMesa(Request $request, $mesaUuid)
    {
        // Verificar permisos
        if (!Auth::check() || Auth::user()->role_id >= 4) {
            return redirect()->back()->with('error', __('No tienes permisos para editar mesas'));
        }

    
        $request->validate([
            'descripcion' => 'required|string|max:100',
            'numero_mesa' => 'required|integer|min:1|max:999',
            'numero_comensales' => 'nullable|integer|min:1|max:50',
            'observaciones' => 'nullable|string|max:255'
        ]);

        try {
            $mesa = Ficha::findOrFail($mesaUuid);

            // Verificar que es una mesa
            if ($mesa->tipo != 5 || $mesa->modo != 'mesa') {
                return redirect()->back()->with('error', __('Esta ficha no es una mesa'));
            }

            $mesa->update([
                'descripcion' => $request->descripcion,
                'numero_mesa' => $request->numero_mesa,
                'numero_comensales' => $request->numero_comensales,
                'observaciones' => $request->observaciones
            ]);

            return redirect()->back()->with('success', __('Mesa actualizada correctamente'));

        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Error al actualizar la mesa: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Eliminar una mesa (solo si está libre)
     */
    public function eliminarMesa($mesaUuid)
    {
        // Verificar permisos
        if (!Auth::check() || Auth::user()->role_id >= 4) {
            return redirect()->back()->with('error', __('No tienes permisos para eliminar mesas'));
        }

        try {
            $mesa = Ficha::findOrFail($mesaUuid);

            // Verificar que es una mesa
            if ($mesa->tipo != 5 || $mesa->modo != 'mesa') {
                return redirect()->back()->with('error', __('Esta ficha no es una mesa'));
            }

            // Verificar que está libre
            if ($mesa->estado_mesa != 'libre') {
                return redirect()->back()->with('error', __('Solo se pueden eliminar mesas en estado libre'));
            }

            // Verificar que no tiene productos/servicios asociados
            if ($mesa->productos()->exists() || $mesa->servicios()->exists()) {
                return redirect()->back()->with('error', __('No se puede eliminar una mesa con consumos registrados'));
            }

            $mesa->delete();

            return redirect()->back()->with('success', __('Mesa eliminada correctamente'));

        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Error al eliminar la mesa: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Reordenar mesas mediante drag & drop
     */
    public function reordenarMesas(Request $request)
    {
        // Verificar permisos
        if (!Auth::check() || Auth::user()->role_id >= 4) {
            return response()->json(['success' => false, 'message' => __('No tienes permisos para reordenar mesas')], 403);
        }

        $request->validate([
            'orden' => 'required|array',
            'orden.*.uuid' => 'required|string',
            'orden.*.orden' => 'required|integer|min:1'
        ]);

        try {
            DB::beginTransaction();
            
            foreach ($request->orden as $item) {
                Ficha::where('uuid', $item['uuid'])
                    ->where('tipo', 5)
                    ->where('modo', 'mesa')
                    ->update(['orden' => $item['orden']]);
            }
            
            DB::commit();
            return response()->json(['success' => true, 'message' => __('Orden actualizado correctamente')]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Notifica a todos los usuarios básicos sobre un nuevo evento
     */
    private function notificarNuevoEvento($ficha)
    {
        try {
            $firebase = app(\App\Services\FirebaseService::class);
            
            // Obtener todos los usuarios básicos (role_id >= 4) con token de Firebase
            $usuariosBasicos = User::where('site_id', app('site')->id)
                ->where('role_id', '>=', 4)
                ->whereNotNull('fcm_token')
                ->get();
            
            foreach ($usuariosBasicos as $usuario) {
                $firebase->sendNotification(
                    $usuario->fcm_token,
                    __('Nuevo evento disponible'),
                    __('Se ha creado un nuevo evento: :evento', ['evento' => $ficha->descripcion]),
                    [
                        'tipo' => 'evento',
                        'evento_id' => $ficha->uuid
                    ]
                );
            }
        } catch (\Exception $e) {
            // Log del error pero no interrumpir el flujo de creación del evento
            \Log::error('Error enviando notificaciones de nuevo evento: ' . $e->getMessage());
        }
    }
}
