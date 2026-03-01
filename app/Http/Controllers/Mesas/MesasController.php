<?php

namespace App\Http\Controllers\Mesas;

use App\Http\Controllers\Controller;
use App\Models\Ficha;
use App\Models\FichaProducto;
use App\Models\FichaRecibo;
use App\Models\FichaServicio;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use App\Jobs\NotificarStockBajo;
use App\Services\MesaService;

class MesasController extends Controller
{
    /**
     * @var MesaService
     */
    protected $mesaService;
    
    public function __construct(MesaService $mesaService)
    {
        $this->mesaService = $mesaService;
    }
    
    /**
     * Mostrar el grid de mesas
     */
    public function index()
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

        // Calcular importe usando MesaService
        $mesas->each(function($mesa) {
            $mesa->importe = $this->mesaService->calcularImporte($mesa);
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
    public function abrir(\App\Http\Requests\AbrirMesaRequest $request, $mesaId)
    {
        try {
            return DB::transaction(function () use ($request, $mesaId) {
                $mesa = Ficha::where('uuid', $mesaId)
                    ->lockForUpdate()
                    ->firstOrFail();
                
                // Verificar autorización
                $this->authorize('abrir', $mesa);
                
                // Abrir mesa usando MesaService
                $this->mesaService->abrir(
                    $mesa,
                    Auth::id(),
                    $request->numero_comensales,
                    $request->notas
                );
                
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
    public function tomar($mesaId)
    {
        try {
            return DB::transaction(function () use ($mesaId) {
                $mesa = Ficha::where('uuid', $mesaId)
                    ->lockForUpdate()
                    ->firstOrFail();
                
                // Verificar autorización
                $this->authorize('tomar', $mesa);
                
                // Transferir mesa usando MesaService
                $this->mesaService->transferir($mesa, Auth::id());
                
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
    public function resumen($mesaId)
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
    public function cerrar(\App\Http\Requests\CerrarMesaRequest $request, $mesaId)
    {
        try {
            return DB::transaction(function () use ($request, $mesaId) {
                $mesa = Ficha::where('uuid', $mesaId)
                    ->with(['productos.producto', 'servicios.servicio'])
                    ->lockForUpdate()
                    ->firstOrFail();
                
                // Verificar autorización
                $this->authorize('cerrar', $mesa);
        
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
    public function liberar($mesaId)
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
                
                // Eliminar productos y servicios de la mesa
                FichaProducto::where('id_ficha', $mesa->uuid)->delete();
                FichaServicio::where('id_ficha', $mesa->uuid)->delete();
                
                // Registrar en historial con datos completos del cierre
                \App\Models\MesaHistorial::create([
                    'mesa_id' => $mesa->uuid,
                    'accion' => 'liberar',
                    'camarero_id' => Auth::id(),
                    'detalles' => [
                        'productos' => $productos->toArray(),
                        'servicios' => $servicios->toArray(),
                        'subtotal' => round($subtotal, 2),
                        'iva_desglose' => $ivaDesglose,
                        'total_iva' => round($totalIva, 2),
                        'total' => round($totalGeneral, 2)
                    ]
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Mesa liberada correctamente'
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Error al liberar mesa: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al liberar la mesa. Por favor, inténtalo de nuevo.'
            ], 500);
        }
    }
    
    /**
     * Generar múltiples mesas automáticamente
     */
    public function generar(Request $request)
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
    public function crearIndividual(Request $request)
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
    public function actualizar(\App\Http\Requests\ActualizarMesaRequest $request, $mesaUuid)
    {
        $mesa = Ficha::findOrFail($mesaUuid);
        
        // Verificar autorización
        $this->authorize('update', $mesa);

        // Verificar que es una mesa
        if ($mesa->tipo != 5 || $mesa->modo != 'mesa') {
            return redirect()->back()->with('error', __('Esta ficha no es una mesa'));
        }

        $mesa->update($request->validated());

        return redirect()->back()->with('success', __('Mesa actualizada correctamente'));
    }

    /**
     * Eliminar una mesa (solo si está libre)
     */
    public function eliminar($mesaUuid)
    {
        $mesa = Ficha::findOrFail($mesaUuid);
        
        // Verificar autorización
        $this->authorize('delete', $mesa);

        // Verificar que es una mesa
        if ($mesa->tipo != 5 || $mesa->modo != 'mesa') {
            return redirect()->back()->with('error', __('Esta ficha no es una mesa'));
        }

        // Verificar que no tiene productos/servicios asociados
        if ($mesa->productos()->exists() || $mesa->servicios()->exists()) {
            return redirect()->back()->with('error', __('No se puede eliminar una mesa con consumos registrados'));
        }

        $mesa->delete();

        return redirect()->back()->with('success', __('Mesa eliminada correctamente'));
    }

    /**
     * Reordenar mesas mediante drag & drop
     */
    public function reordenar(Request $request)
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
