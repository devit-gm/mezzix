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
    $estaEnFicha = $ficha->inscritos->where('user_id', Auth::id())->isNotEmpty();

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

    $ficha->precio = $this->ObtenerImporteFicha($ficha);

    // Borrable
    $esAdmin = $user && $user->role_id == 1;
    $esPropietario = Auth::id() == $ficha->user_id;
    $estaEnFicha = $ficha->inscritos->where('user_id', Auth::id())->isNotEmpty();

    $ficha->borrable = ($esPropietario || $esAdmin || $estaEnFicha) && $ficha->estado == 0;

    // Calcular comensales
    $usuariosFicha = ($ficha->tipo == 1)
        ? collect([$ficha->usuario])->filter()
        : $ficha->inscritos;

    $ficha->total_comensales = $usuariosFicha->sum(fn($u) => 1 + ($u->invitados ?? 0) + ($u->ninos ?? 0));
    $ficha->total_ninos      = $usuariosFicha->sum(fn($u) => $u->ninos ?? 0);
}

// Si son fichas cerradas, limitar a 20 más recientes después del filtro de permisos
if ($request->method() == "POST" && $request->incluir_cerradas == 1) {
    $fichas = array_slice($fichas, 0, 20);
}

        $errors = new \Illuminate\Support\MessageBag();
        if ($fichas == null || count($fichas) == 0) {
            $errors->add('msg', __('No se encontraron fichas para mostrar.'));
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
            'tipo' => 'required'
        ]);

        $descripcion = '';
        if ($request->descripcion == null) {
            $descripcion = '';
        } else {
            $descripcion = $request->descripcion;
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
            'responsables' => $request->responsables
        ]);
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
            'tipo' => 'required'
        ]);
        $ficha = Ficha::find($uuid);

        if ($request->descripcion == null) {
            $ficha->descripcion = '';
        } else {
            $ficha->descripcion = $request->descripcion;
        }
        $descripcion = $ficha->descripcion;

        $ficha->update([
            'descripcion' => $descripcion,
            'user_id' => $request->user_id,
            'precio' =>  $this->ObtenerImporteFicha($ficha),
            'invitados_grupo' => $request->invitados_grupo,
            'estado' => $request->estado,
            'tipo' => $request->tipo,
            'fecha' => $request->fecha,
            'hora' => $request->hora,
            'menu' => $request->menu,
            'responsables' => $request->responsables
        ]);
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
        return view('fichas.create', compact('userId', 'currentDateTime'));
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
        $ficha->precio = $this->ObtenerImporteFicha($ficha);
        $fechaCambiada = Carbon::parse($ficha->fecha)->todateString();
        if ($ficha->user_id == Auth::id() || (Auth::check() && Auth::user()->role_id == 1) || FichaUsuario::where('id_ficha', $ficha->uuid)->where('user_id', Auth::id())->exists()) {
            $ficha->borrable = true;
        } else {
            $ficha->borrable = false;
        }

        $userTimezone = 'Europe/Madrid';
        $currentDateTime = Carbon::now($userTimezone);
        return view('fichas.edit', compact('ficha', 'fechaCambiada', 'currentDateTime'));
    }

    private function ObtenerImporteFicha($ficha, $sumarInvitados = false)
    {
        // Usar ajustes cacheados si están disponibles
        $ajustes = app()->has('ajustes') ? app('ajustes') : Ajustes::first();
        
        // Usar sum() en lugar de loops para mejor rendimiento
        $precio = FichaProducto::where('id_ficha', $ficha->uuid)->sum('precio');
        $precio += FichaServicio::where('id_ficha', $ficha->uuid)->sum('precio');
        $precio += FichaGasto::where('id_ficha', $ficha->uuid)->sum('precio');
        
        // Solo procesar invitados si es necesario
        if ($sumarInvitados) {
            $usuarios = FichaUsuario::where('id_ficha', $ficha->uuid)->get(['invitados']);
            foreach ($usuarios as $usuario) {
                $num_invitados = $usuario->invitados;
                if ($num_invitados > $ajustes->max_invitados_cobrar) {
                    $num_invitados = $ajustes->max_invitados_cobrar;
                }
                if ($ajustes->primer_invitado_gratis && $num_invitados > 0) {
                    $num_invitados--;
                }
                $precio += $num_invitados * $ajustes->precio_invitado;
            }
        }
        
        if ($ajustes->activar_invitados_grupo && $ficha->invitados_grupo > 0) {
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
        
        if ($ajustes->permitir_comprar_sin_stock == 1) {
            $productos = Producto::where('familia', $uuid2)
                ->where(function($query) {
                    $query->where('precio', '>', 0)
                          ->orWhere('combinado', 1); // Incluir productos combinados aunque tengan precio 0
                })
                ->orderBy('posicion')
                ->get();
            $productosAgotados = collect();
            $productosStockBajo = collect();
        } else {
            $productos = Producto::where('familia', $uuid2)
                ->where(function($query) {
                    $query->where('precio', '>', 0)
                          ->orWhere('combinado', 1); // Incluir productos combinados aunque tengan precio 0
                })
                ->orderBy('posicion')
                ->get();
            
            // Productos sin stock (no se pueden comprar)
            $productosAgotados = Producto::where('familia', $uuid2)
                ->where(function ($query) {
                    $query->where(function ($query) {
                        $query->where('combinado', 0)->where('stock', '<=', 0);
                    })->orWhere(function ($query) {
                        $query->where('combinado', 1)
                            ->whereIn('uuid', function ($subquery) {
                                $subquery->select('id_producto')
                                    ->from('composicion_productos')
                                    ->groupBy('id_producto')
                                    ->havingRaw('SUM(CASE WHEN id_componente IN (SELECT uuid FROM productos WHERE stock <= 0) THEN 1 ELSE 0 END) > 0');
                            });
                    });
                })->orderBy('posicion')->get();
            
            // Productos con stock bajo (pueden comprarse pero con aviso)
            $productosStockBajo = Producto::where('familia', $uuid2)
                ->where(function ($query) use ($stockMinimo) {
                    $query->where(function ($query) use ($stockMinimo) {
                        $query->where('combinado', 0)
                            ->where('stock', '>', 0)
                            ->where('stock', '<=', $stockMinimo);
                    })->orWhere(function ($query) use ($stockMinimo) {
                        $query->where('combinado', 1)
                            ->whereIn('uuid', function ($subquery) use ($stockMinimo) {
                                $subquery->select('id_producto')
                                    ->from('composicion_productos')
                                    ->groupBy('id_producto')
                                    ->havingRaw('SUM(CASE WHEN id_componente IN (SELECT uuid FROM productos WHERE stock > 0 AND stock <= ?) THEN 1 ELSE 0 END) > 0', [$stockMinimo]);
                            });
                    });
                })
                ->whereNotIn('uuid', $productosAgotados->pluck('uuid'))
                ->orderBy('posicion')
                ->get();
        }
        
        return view('fichas.productos', compact('ficha', 'familia', 'productos', 'productosAgotados', 'productosStockBajo', 'ajustes'));
    }

    public function usuarios($uuid)
    {
        $ficha = Ficha::find($uuid);
        $ajustes = DB::connection('site')->table('ajustes')->first();
     
        $ficha->precio = $this->ObtenerImporteFicha($ficha);
        $site = app('site');
        //Si es una ficha individual sólo mostramos al usuario activo
        if ($ficha->tipo == 1) {
            $usuariosFicha = User::where('site_id', $site->id)->where('id', $ficha->user_id)->get();
        } else {
            $usuariosFicha = User::where('site_id', $site->id)->orderBy('id')->get();
        }
        //Si la ficha está cerrada (estado = 1) solo mostramos los usuarios que están en FichaUsuario
        if ($ficha->estado == 1) {
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

            //Descontamos el stock de cada artículo consumido
            $productos = FichaProducto::with(['producto.composicion.componenteProducto'])
                ->where('id_ficha', $uuid)
                ->get();
            
            Log::info('=== INICIO DESCUENTO STOCK ===', [
                'ficha_uuid' => $uuid,
                'productos_count' => $productos->count()
            ]);
            
            $stockService = new \App\Services\StockNotificationService();
            
            foreach ($productos as $producto) {
                $productoFicha = $producto->producto;
                if (!$productoFicha) continue;
                
                if ($productoFicha->combinado == 1) {
                    foreach ($productoFicha->composicion as $composicion) {
                        $producto2 = $composicion->componenteProducto;
                        if ($producto2) {
                            $stockAnterior = $producto2->stock;
                            $producto2->stock -= $producto->cantidad;
                            $producto2->save();
                            
                            Log::info('Stock actualizado (componente)', [
                                'producto' => $producto2->nombre,
                                'stock_anterior' => $stockAnterior,
                                'stock_nuevo' => $producto2->stock,
                                'cantidad_descontada' => $producto->cantidad
                            ]);
                            
                            // Verificar stock bajo
                            $stockService->verificarYNotificar($producto2->uuid);
                        }
                    }
                } else {
                    $stockAnterior = $productoFicha->stock;
                    $productoFicha->stock -= $producto->cantidad;
                    $productoFicha->save();
                    
                    Log::info('Stock actualizado', [
                        'producto' => $productoFicha->nombre,
                        'stock_anterior' => $stockAnterior,
                        'stock_nuevo' => $productoFicha->stock,
                        'cantidad_descontada' => $producto->cantidad
                    ]);
                    
                    // Verificar stock bajo
                    $stockService->verificarYNotificar($productoFicha->uuid);
                }
            }
            
            Log::info('=== FIN DESCUENTO STOCK ===');
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
        if ($usuariosFicha == null) {
            $usuariosFicha = User::where('id', $ficha->user_id)->get();
        } else {
            $usuariosFicha = [];
            //Buscar los usuarios que están dentro de FichaUsuario
            $usuarios = User::where('site_id', $site->id)->orderBy('id')->get();
            foreach ($usuarios as $usuario) {
                //si el user_id está en FichaUsuario de la ficha lo ponemos como marcado
                $fichaUsuario = FichaUsuario::where('id_ficha', $ficha->uuid)->where('user_id', $usuario->id)->first();
                if ($fichaUsuario) {
                    $usuariosFicha[] = $usuario;
                }
            }
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
                'ticket' => 'required|image|mimes:png,jpg,jpeg|max:2048',
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
        FichaUsuario::where('id_ficha', $uuid)->delete();
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
            }
        }
        $ficha = Ficha::find($uuid);
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
        $ficha = Ficha::find($request->idFicha);
        $familia = Familia::find($request->idFamilia);
        $producto = Producto::find($request->idProducto);
        //Si el producto es combinado hay que sumar el precio de sus componentes
        if ($producto->combinado == 1) {
            $productosCombinados = DB::connection('site')->table('composicion_productos')->where('id_producto', $producto->uuid)->get();
            $precio = 0;
            foreach ($productosCombinados as $productoCombinado) {
                $producto2 = Producto::find($productoCombinado->id_componente);
                $precio += $producto2->precio;
            }
            $producto->precio = $precio;
        }
        $cantidad = $request->cantidad;
        $existe = FichaProducto::where('id_ficha', $ficha->uuid)->where('id_producto', $producto->id)->first();
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
        $productos = Producto::where('familia', $familia)->orderBy('posicion')->get();
        return redirect()->route('fichas.productos', [
            'uuid' => $ficha,
            'uuid2' => $familia
        ])->with('success', $cantidad . 'x ' . $producto->nombre . ' ' . __('añadido a la ficha'));
    }

    public function lista($uuid)
    {
        $ficha = Ficha::find($uuid);
        $ficha->precio = $this->ObtenerImporteFicha($ficha);
        $ajustes = DB::connection('site')->table('ajustes')->first();
        if($ajustes->modo_operacion == 'mesas'){
            $productosFicha = FichaProducto::where('id_ficha', $uuid)
           ->get();
        }else{
            $productosFicha = FichaProducto::where('id_ficha', $uuid)
            ->groupBy('id_producto')
            ->selectRaw('id_producto, sum(cantidad) as cantidad, sum(precio) as precio') // Calculate the total quantity and total price
            ->get();
        }
        foreach ($productosFicha as $productoFicha) {
            $productoFicha->borrable = true;
            $productoFicha->producto = Producto::find($productoFicha->id_producto);
        }

        $ajustes = DB::connection('site')->table('ajustes')->first();
        return view('fichas.lista', compact('ficha', 'productosFicha', 'ajustes'));
    }

    public function destroylista(string $uuid, string $uuid2)
    {
        // Verificar si uuid2 es un UUID de ficha_producto (modo mesas) o un id_producto (modo fichas)
        $fichaProducto = FichaProducto::where('uuid', $uuid2)->first();
        
        if ($fichaProducto && $fichaProducto->id_ficha === $uuid) {
            // Es un UUID de ficha_producto (modo mesas) - borrar solo ese registro
            $fichaProducto->delete();
        } else {
            // Es un id_producto (modo fichas) - borrar todos los registros con ese producto
            $fichaProductos = FichaProducto::where('id_ficha', $uuid)->where('id_producto', $uuid2)->get();
            foreach ($fichaProductos as $fichaProducto) {
                $fichaProducto->delete();
            }
        }
        
        return redirect()->route('fichas.lista', $uuid)
            ->with('success', __('Producto eliminado de la ficha'));
    }

    public function updatelista(string $uuid, string $uuid2, int $cantidad)
    {
        //Buscar el total del producto de la ficha en FichaProducto
        //Si la cantidad es positiva insertar un elemento en FichaProducto
        //Si la cantidad es negativa eliminar un elemento en FichaProducto
        $total = FichaProducto::where('id_ficha', $uuid)->where('id_producto', $uuid2)->sum('cantidad');
        $producto = Producto::find($uuid2);
        if ($producto->combinado == 1) {
            $productosCombinados = DB::connection('site')->table('composicion_productos')->where('id_producto', $producto->uuid)->get();
            $precio = 0;
            foreach ($productosCombinados as $productoCombinado) {
                $producto2 = Producto::find($productoCombinado->id_componente);
                $precio += $producto2->precio;
            }
            $producto->precio = $precio;
        }
        if ($cantidad > 0) {
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
            $fichaProductos = FichaProducto::where('id_ficha', $uuid)->where('id_producto', $uuid2)->take(abs($cantidad))->get();
            foreach ($fichaProductos as $fichaProducto) {
                $fichaProducto->delete();
            }
            return redirect()->route('fichas.lista', $uuid)
                ->with('success', __('Producto eliminado de la ficha'));
        }
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
            'camarero' => $mesa->camarero->name ?? 'N/A',
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
        
                // Descontar stock de productos consumidos con locking
                foreach ($mesa->productos as $fichaProducto) {
                    // Lock del producto para evitar race conditions en stock
                    $producto = Producto::where('uuid', $fichaProducto->id_producto)
                        ->lockForUpdate()
                        ->first();
                    
                    if ($producto) {
                        if ($producto->combinado == 1) {
                            // Producto combinado: descontar componentes
                            $productosCombinados = DB::connection('site')
                                ->table('composicion_productos')
                                ->where('id_producto', $producto->uuid)
                                ->get();
                            
                            foreach ($productosCombinados as $productoCombinado) {
                                // Lock de cada componente
                                $componente = Producto::where('uuid', $productoCombinado->id_componente)
                                    ->lockForUpdate()
                                    ->first();
                                
                                if ($componente) {
                                    // Verificar que haya stock suficiente
                                    if ($componente->stock < $fichaProducto->cantidad) {
                                        throw new \Exception('Stock insuficiente para ' . $componente->nombre);
                                    }
                                    
                                    $componente->stock -= $fichaProducto->cantidad;
                                    $componente->save();
                                    
                                    // Verificar stock bajo
                                    $stockService = new \App\Services\StockNotificationService();
                                    $stockService->verificarYNotificar($componente->uuid);
                                }
                            }
                        } else {
                            // Producto simple: verificar y descontar stock
                            if ($producto->stock < $fichaProducto->cantidad) {
                                throw new \Exception('Stock insuficiente para ' . $producto->nombre);
                            }
                            
                            $producto->stock -= $fichaProducto->cantidad;
                            $producto->save();
                            
                            // Verificar stock bajo
                            $stockService = new \App\Services\StockNotificationService();
                            $stockService->verificarYNotificar($producto->uuid);
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
}
