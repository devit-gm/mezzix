<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Ficha;
use App\Models\FichaRecibo;
use App\Models\Recibo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Ui\Presets\React;

class InformesController extends Controller
{
    public function index(Request $request)
    {
        $site = get_site();
        $ajustes = get_ajustes();
        
        if($ajustes->facturar_ficha_automaticamente){
            // Si la facturación automática de fichas está activada, no mostrar el botón de facturar
            $mostrarBotonFacturar = false;
        } else {
            // Verificar si hay recibos pendientes de facturar
            if (Recibo::where('estado', 0)->count() != 0) {
                $mostrarBotonFacturar = true;
            } else {
                $mostrarBotonFacturar = false;
            }
        }
        
        // Obtener usuarios según el rol
        $user = Auth::user();
        if ($user && $user->role_id > 3) {
            $usuariosInforme = User::where('site_id', $site->id)->where('id', Auth::id())->get();
        } else {
            $usuariosInforme = User::where('site_id', $site->id)->orderBy('id')->get();
        }
        
        // Si se envió el formulario con filtros de fecha
        if ($request->isMethod('put') && $request->has('fecha_inicial')) {
            $request->validate([
                'fecha_inicial' => 'required|date',
                'fecha_final' => 'required|date|after_or_equal:fecha_inicial',
            ]);

            foreach ($usuariosInforme as $usuarioFicha) {
                // Si la facturación es automática, mostrar todos los recibos (ya están facturados)
                if ($ajustes->facturar_ficha_automaticamente) {
                    $usuarioFicha->gastos = Recibo::where('user_id', $usuarioFicha->id)
                        ->whereDate('fecha', '>=', $request->fecha_inicial)
                        ->whereDate('fecha', '<=', $request->fecha_final)
                        ->where('tipo', 1)
                        ->sum('precio');
                    $usuarioFicha->compras = Recibo::where('user_id', $usuarioFicha->id)
                        ->whereDate('fecha', '>=', $request->fecha_inicial)
                        ->whereDate('fecha', '<=', $request->fecha_final)
                        ->where('tipo', 2)
                        ->sum('precio');
                } else {
                    // Si no es automática, respetar el filtro de incluir_facturados
                    if ($request->incluir_facturados == 1) {
                        $usuarioFicha->gastos = Recibo::where('user_id', $usuarioFicha->id)
                            ->whereDate('fecha', '>=', $request->fecha_inicial)
                            ->whereDate('fecha', '<=', $request->fecha_final)
                            ->where('tipo', 1)
                            ->sum('precio');
                        $usuarioFicha->compras = Recibo::where('user_id', $usuarioFicha->id)
                            ->whereDate('fecha', '>=', $request->fecha_inicial)
                            ->whereDate('fecha', '<=', $request->fecha_final)
                            ->where('tipo', 2)
                            ->sum('precio');
                    } else {
                        $usuarioFicha->gastos = Recibo::where('user_id', $usuarioFicha->id)
                            ->where('estado', 0)
                            ->whereDate('fecha', '>=', $request->fecha_inicial)
                            ->whereDate('fecha', '<=', $request->fecha_final)
                            ->where('tipo', 1)
                            ->sum('precio');
                        $usuarioFicha->compras = Recibo::where('user_id', $usuarioFicha->id)
                            ->where('estado', 0)
                            ->whereDate('fecha', '>=', $request->fecha_inicial)
                            ->whereDate('fecha', '<=', $request->fecha_final)
                            ->where('tipo', 2)
                            ->sum('precio');
                    }
                }
                $usuarioFicha->balance = $usuarioFicha->gastos - $usuarioFicha->compras;
            }
        } else {
            // Sin filtros
            foreach ($usuariosInforme as $usuarioFicha) {
                // Si la facturación es automática, mostrar todos los recibos
                if ($ajustes->facturar_ficha_automaticamente) {
                    $usuarioFicha->gastos = Recibo::where('user_id', $usuarioFicha->id)
                        ->where('tipo', 1)
                        ->sum('precio');
                    $usuarioFicha->compras = Recibo::where('user_id', $usuarioFicha->id)
                        ->where('tipo', 2)
                        ->sum('precio');
                } else {
                    // Si no es automática, mostrar solo recibos no facturados
                    $usuarioFicha->gastos = Recibo::where('user_id', $usuarioFicha->id)
                        ->where('estado', 0)
                        ->where('tipo', 1)
                        ->sum('precio');
                    $usuarioFicha->compras = Recibo::where('user_id', $usuarioFicha->id)
                        ->where('estado', 0)
                        ->where('tipo', 2)
                        ->sum('precio');
                }
                $usuarioFicha->balance = $usuarioFicha->gastos - $usuarioFicha->compras;
            }
        }
        
        return view('informes.index', compact('mostrarBotonFacturar', 'usuariosInforme', 'request', 'ajustes'));
    }

    public function facturar(Request $request)
    {
        // Cambiar el estado de los recibos a facturados
        // Todos los recibos que tengan estado 0, se cambian a estado 1
        $recibos = FichaRecibo::where('estado', 0)->get();

        foreach ($recibos as $recibo) {
            $recibo->estado = 1;
            $recibo->save();
        }
        return redirect()->route('informes.index')->with('success', __('Facturación realizada correctamente'));
    }

    /**
     * Informe de ventas por producto
     */
    public function informeProductos(Request $request)
    {
        $ajustes = get_ajustes();
        
        // Verificar que estamos en modo mesas
        if ($ajustes->modo_operacion !== 'mesas') {
            return redirect()->route('informes.index');
        }

        $fechaInicial = $request->input('fecha_inicial', now()->startOfMonth()->format('Y-m-d'));
        $fechaFinal = $request->input('fecha_final', now()->format('Y-m-d'));

        // 1. Obtener ventas ACTUALES (mesas aún abiertas o cerradas pero no liberadas) con IVA
        $ventasActuales = DB::connection('site')
            ->table('fichas_productos')
            ->join('productos', 'fichas_productos.id_producto', '=', 'productos.uuid')
            ->join('fichas', 'fichas_productos.id_ficha', '=', 'fichas.uuid')
            ->join('familias', 'productos.familia', '=', 'familias.uuid')
            ->whereDate('fichas.fecha', '>=', $fechaInicial)
            ->whereDate('fichas.fecha', '<=', $fechaFinal)
            ->where('fichas.modo', 'mesa')
            ->whereIn('fichas.estado_mesa', ['cerrada', 'ocupada']) // Mesas que aún tienen productos
            ->select(
                'productos.uuid',
                'productos.nombre as producto',
                'productos.precio',
                'productos.iva',
                'familias.nombre as familia',
                DB::raw('SUM(fichas_productos.cantidad) as cantidad_vendida'),
                DB::raw('SUM(fichas_productos.cantidad * fichas_productos.precio / (1 + productos.iva / 100)) as base_imponible'),
                DB::raw('SUM(fichas_productos.cantidad * fichas_productos.precio - (fichas_productos.cantidad * fichas_productos.precio / (1 + productos.iva / 100))) as importe_iva'),
                DB::raw('SUM(fichas_productos.cantidad * fichas_productos.precio) as total_vendido')
            )
            ->groupBy('productos.uuid', 'productos.nombre', 'productos.precio', 'productos.iva', 'familias.nombre')
            ->get();

        // 2. Obtener ventas HISTÓRICAS (de mesas ya liberadas)
        $historial = DB::connection('site')
            ->table('mesa_historial')
            ->whereDate('fecha_accion', '>=', $fechaInicial)
            ->whereDate('fecha_accion', '<=', $fechaFinal)
            ->where('accion', 'liberar')
            ->whereNotNull('detalles')
            ->get();

        // Procesar historial para extraer productos con IVA
        $ventasHistoricas = collect();
        foreach ($historial as $registro) {
            $detalles = json_decode($registro->detalles, true);
            if (isset($detalles['productos']) && is_array($detalles['productos'])) {
                foreach ($detalles['productos'] as $prod) {
                    // Buscar si ya existe este producto en la colección
                    $productoExistente = $ventasHistoricas->firstWhere('producto_id', $prod['producto_id']);
                    
                    $iva = $prod['iva'] ?? 0;
                    // Si viene de historial reciente con base_imponible, usarlo; si no, calcular desde PVP
                    if (isset($prod['base_imponible'])) {
                        $baseImponible = $prod['base_imponible'];
                        $importeIva = $prod['importe_iva'] ?? 0;
                        $total = $prod['total'] ?? ($baseImponible + $importeIva);
                    } else {
                        // Calcular desde PVP (precio con IVA incluido)
                        $pvp = $prod['cantidad'] * $prod['precio'];
                        $baseImponible = $pvp / (1 + $iva / 100);
                        $importeIva = $pvp - $baseImponible;
                        $total = $pvp;
                    }
                    
                    if ($productoExistente) {
                        // Sumar cantidades y totales
                        $productoExistente->cantidad_vendida += $prod['cantidad'];
                        $productoExistente->base_imponible += $baseImponible;
                        $productoExistente->importe_iva += $importeIva;
                        $productoExistente->total_vendido += $total;
                    } else {
                        // Agregar nuevo producto
                        $ventasHistoricas->push((object)[
                            'uuid' => $prod['producto_id'],
                            'producto' => $prod['nombre'],
                            'precio' => $prod['precio'],
                            'iva' => $iva,
                            'familia' => null, // No tenemos familia en historial
                            'cantidad_vendida' => $prod['cantidad'],
                            'base_imponible' => $baseImponible,
                            'importe_iva' => $importeIva,
                            'total_vendido' => $total
                        ]);
                    }
                }
            }
        }

        // 3. Combinar ventas actuales con históricas
        $ventasProductos = collect();
        
        // Agregar ventas actuales
        foreach ($ventasActuales as $venta) {
            $ventasProductos->push($venta);
        }
        
        // Agregar o sumar ventas históricas
        foreach ($ventasHistoricas as $ventaHist) {
            $productoExistente = $ventasProductos->firstWhere('uuid', $ventaHist->uuid);
            
            if ($productoExistente) {
                // Ya existe, sumar
                $productoExistente->cantidad_vendida += $ventaHist->cantidad_vendida;
                $productoExistente->base_imponible += $ventaHist->base_imponible;
                $productoExistente->importe_iva += $ventaHist->importe_iva;
                $productoExistente->total_vendido += $ventaHist->total_vendido;
            } else {
                // No existe, agregar
                // Buscar datos completos del producto para tener familia e IVA actual
                $producto = DB::connection('site')->table('productos')
                    ->join('familias', 'productos.familia', '=', 'familias.uuid')
                    ->where('productos.uuid', $ventaHist->uuid)
                    ->select('familias.nombre as familia', 'productos.iva')
                    ->first();
                    
                $ventaHist->familia = $producto ? $producto->familia : 'Sin familia';
                $ventaHist->iva = $producto ? $producto->iva : $ventaHist->iva;
                $ventasProductos->push($ventaHist);
            }
        }
        
        // Ordenar por total vendido
        $ventasProductos = $ventasProductos->sortByDesc('total_vendido')->values();
        
        $totalGeneral = $ventasProductos->sum('total_vendido');
        $cantidadTotal = $ventasProductos->sum('cantidad_vendida');
        $subtotalGeneral = $ventasProductos->sum('base_imponible');
        $totalIvaGeneral = $ventasProductos->sum('importe_iva');
        
        return view('informes.ventas-productos', compact('ventasProductos', 'totalGeneral', 'cantidadTotal', 'subtotalGeneral', 'totalIvaGeneral', 'fechaInicial', 'fechaFinal'));
    }

    /**
     * Informe de ventas por camarero
     */
    public function ventasCamareros(Request $request)
    {
        $ajustes = get_ajustes();
        
        if ($ajustes->modo_operacion !== 'fichas') {
            return redirect()->route('informes.index');
        }

        $fechaInicial = $request->input('fecha_inicial', now()->startOfMonth()->format('Y-m-d'));
        $fechaFinal = $request->input('fecha_final', now()->format('Y-m-d'));

        // Obtener ventas por camarero desde fichas
        $ventasPorCamarero = DB::connection('site')
            ->table('fichas')
            ->whereDate('fichas.fecha', '>=', $fechaInicial)
            ->whereDate('fichas.fecha', '<=', $fechaFinal)
            ->where('fichas.tipo', 5)
            ->where('fichas.estado', 1)
            ->whereNotNull('fichas.camarero_id')
            ->select(
                'fichas.camarero_id',
                DB::raw('COUNT(DISTINCT fichas.uuid) as mesas_atendidas'),
                DB::raw('SUM(fichas.precio) as total_vendido'),
                DB::raw('AVG(fichas.precio) as ticket_medio')
            )
            ->groupBy('fichas.camarero_id')
            ->get()
            ->keyBy('camarero_id');

        // Obtener nombres de camareros desde la base de datos principal
        $camareroIds = $ventasPorCamarero->keys()->toArray();
        $camareros = DB::table('users')
            ->whereIn('id', $camareroIds)
            ->pluck('name', 'id');

        // Combinar datos
        $ventasCamareros = $ventasPorCamarero->map(function ($venta) use ($camareros) {
            return (object) [
                'id' => $venta->camarero_id,
                'camarero' => $camareros[$venta->camarero_id] ?? 'Desconocido',
                'mesas_atendidas' => $venta->mesas_atendidas,
                'total_vendido' => $venta->total_vendido,
                'ticket_medio' => $venta->ticket_medio
            ];
        })->sortByDesc('total_vendido')->values();

        $totalGeneral = $ventasCamareros->sum('total_vendido');
        $mesasTotal = $ventasCamareros->sum('mesas_atendidas');

        return view('informes.ventas-camareros', compact('ventasCamareros', 'totalGeneral', 'mesasTotal', 'fechaInicial', 'fechaFinal'));
    }

    /**
     * Informe de ocupación de mesas
     */
    public function ocupacionMesas(Request $request)
    {
        $ajustes = DB::connection('site')->table('ajustes')->first();
        
        if ($ajustes->modo_operacion !== 'mesas') {
            return redirect()->route('informes.index');
        }

        $fechaInicial = $request->input('fecha_inicial', now()->startOfDay()->format('Y-m-d H:i:s'));
        $fechaFinal = $request->input('fecha_final', now()->format('Y-m-d H:i:s'));

        // Estadísticas por mesa
        $estadisticasMesas = DB::connection('site')
            ->table('fichas')
            ->whereDate('fecha', '>=', $fechaInicial)
            ->whereDate('fecha', '<=', $fechaFinal)
            ->where('tipo', 5)
            ->where('estado', 1)
            ->select(
                'numero_mesa',
                'descripcion',
                DB::raw('COUNT(*) as veces_ocupada'),
                DB::raw('SUM(precio) as total_recaudado'),
                DB::raw('AVG(precio) as ticket_medio'),
                DB::raw('AVG(TIMESTAMPDIFF(MINUTE, hora_apertura, hora_cierre)) as tiempo_medio_ocupacion')
            )
            ->groupBy('numero_mesa', 'descripcion')
            ->orderByDesc('total_recaudado')
            ->get();

        // Estadísticas generales
        $mesasTotales = DB::connection('site')
            ->table('fichas')
            ->where('tipo', 5)
            ->where('modo', 'mesa')
            ->count();

        $ocupacionTotal = $estadisticasMesas->sum('veces_ocupada');
        $recaudacionTotal = $estadisticasMesas->sum('total_recaudado');

        return view('informes.ocupacion-mesas', compact('estadisticasMesas', 'mesasTotales', 'ocupacionTotal', 'recaudacionTotal', 'fechaInicial', 'fechaFinal'));
    }

    /**
     * Informe de horas pico
     */
    public function horasPico(Request $request)
    {
        $ajustes = DB::connection('site')->table('ajustes')->first();
        
        if ($ajustes->modo_operacion !== 'mesas') {
            return redirect()->route('informes.index');
        }

        $fechaInicial = $request->input('fecha_inicial', now()->startOfMonth()->format('Y-m-d'));
        $fechaFinal = $request->input('fecha_final', now()->format('Y-m-d'));

        // Ventas por hora
        $ventasPorHora = DB::connection('site')
            ->table('fichas')
            ->whereDate('fecha', '>=', $fechaInicial)
            ->whereDate('fecha', '<=', $fechaFinal)
            ->where('tipo', 5)
            ->where('estado', 1)
            ->whereNotNull('hora_apertura')
            ->select(
                DB::raw('HOUR(hora_apertura) as hora'),
                DB::raw('COUNT(*) as mesas_abiertas'),
                DB::raw('SUM(precio) as total_vendido'),
                DB::raw('AVG(precio) as ticket_medio')
            )
            ->groupBy(DB::raw('HOUR(hora_apertura)'))
            ->orderBy('hora')
            ->get();

        // Ventas por día de la semana
        $ventasPorDia = DB::connection('site')
            ->table('fichas')
            ->whereDate('fecha', '>=', $fechaInicial)
            ->whereDate('fecha', '<=', $fechaFinal)
            ->where('tipo', 5)
            ->where('estado', 1)
            ->select(
                DB::raw('DAYOFWEEK(fecha) as dia_semana'),
                DB::raw('COUNT(*) as mesas_cerradas'),
                DB::raw('SUM(precio) as total_vendido'),
                DB::raw('AVG(precio) as ticket_medio')
            )
            ->groupBy(DB::raw('DAYOFWEEK(fecha)'))
            ->orderBy('dia_semana')
            ->get();

        // Mapear nombres de días
        $diasSemana = [
            1 => __('Domingo'),
            2 => __('Lunes'),
            3 => __('Martes'),
            4 => __('Miércoles'),
            5 => __('Jueves'),
            6 => __('Viernes'),
            7 => __('Sábado')
        ];

        $ventasPorDia = $ventasPorDia->map(function($item) use ($diasSemana) {
            $item->dia_nombre = $diasSemana[$item->dia_semana];
            return $item;
        });

        return view('informes.horas-pico', compact('ventasPorHora', 'ventasPorDia', 'fechaInicial', 'fechaFinal'));
    }

    /**
     * Informe de ventas por producto (modo fichas)
     */
    public function ventasProductosFichas(Request $request)
    {
        $ajustes = DB::connection('site')->table('ajustes')->first();
        
        // Verificar que NO estamos en modo mesas
        if ($ajustes->modo_operacion === 'mesas') {
            return redirect()->route('informes.index');
        }

        $fechaInicial = $request->input('fecha_inicial', now()->startOfMonth()->format('Y-m-d'));
        $fechaFinal = $request->input('fecha_final', now()->format('Y-m-d'));

        // Obtener ventas por producto desde fichas_productos
        $ventasProductos = DB::connection('site')
            ->table('fichas_productos')
            ->join('productos', 'fichas_productos.id_producto', '=', 'productos.uuid')
            ->join('fichas', 'fichas_productos.id_ficha', '=', 'fichas.uuid')
            ->join('familias', 'productos.familia', '=', 'familias.uuid')
            ->whereDate('fichas.fecha', '>=', $fechaInicial)
            ->whereDate('fichas.fecha', '<=', $fechaFinal)
            ->where('fichas.tipo', '<', 5) // Excluir fichas de mesas (tipo 5)
            ->where('fichas.estado', 1) // Estado cerrado
            ->select(
                'productos.uuid',
                'productos.nombre as producto',
                'productos.combinado',
                'familias.nombre as familia',
                DB::raw('SUM(fichas_productos.cantidad) as cantidad_vendida'),
                DB::raw('SUM(fichas_productos.precio) as total_vendido'),
                DB::raw('AVG(fichas_productos.precio / fichas_productos.cantidad) as precio')
            )
            ->groupBy('productos.uuid', 'productos.nombre', 'productos.combinado', 'familias.nombre')
            ->orderByDesc('total_vendido')
            ->get();

        $totalGeneral = $ventasProductos->sum('total_vendido');
        $cantidadTotal = $ventasProductos->sum('cantidad_vendida');

        return view('informes.ventas-productos-fichas', compact('ventasProductos', 'totalGeneral', 'cantidadTotal', 'fechaInicial', 'fechaFinal'));
    }

    /**
     * Informe de ventas por socio (modo fichas)
     */
    public function ventasSocios(Request $request)
    {
        $ajustes = DB::connection('site')->table('ajustes')->first();
        
        if ($ajustes->modo_operacion === 'mesas') {
            return redirect()->route('informes.index');
        }

        $fechaInicial = $request->input('fecha_inicial', now()->startOfMonth()->format('Y-m-d'));
        $fechaFinal = $request->input('fecha_final', now()->format('Y-m-d'));

        // Obtener ventas por socio desde fichas
        $ventasPorSocio = DB::connection('site')
            ->table('fichas')
            ->whereDate('fichas.fecha', '>=', $fechaInicial)
            ->whereDate('fichas.fecha', '<=', $fechaFinal)
            ->where('fichas.tipo', '<', 5) // Excluir fichas de mesas (tipo 5) y de compras (tipo 3)
            ->where('fichas.tipo', '!=', 3) // Excluir fichas de compras (tipo 3)
            ->where('fichas.estado', 1) // Estado cerrado
            ->whereNotNull('fichas.user_id')
            ->select(
                'fichas.user_id',
                DB::raw('COUNT(DISTINCT fichas.uuid) as total_compras'),
                DB::raw('SUM(fichas.precio) as total_gastado'),
                DB::raw('AVG(fichas.precio) as ticket_medio')
            )
            ->groupBy('fichas.user_id')
            ->get()
            ->keyBy('user_id');

        // Obtener nombres de socios desde la base de datos principal
        $socioIds = $ventasPorSocio->keys()->toArray();
        $socios = DB::table('users')
            ->whereIn('id', $socioIds)
            ->pluck('name', 'id');

        // Combinar datos
        $ventasSocios = $ventasPorSocio->map(function ($venta) use ($socios) {
            return (object) [
                'id' => $venta->user_id,
                'socio' => $socios[$venta->user_id] ?? 'Desconocido',
                'total_compras' => $venta->total_compras,
                'total_gastado' => $venta->total_gastado,
                'ticket_medio' => $venta->ticket_medio
            ];
        })->sortByDesc('total_gastado')->values();

        $totalGeneral = $ventasSocios->sum('total_gastado');
        $comprasTotal = $ventasSocios->sum('total_compras');

        return view('informes.ventas-socios', compact('ventasSocios', 'totalGeneral', 'comprasTotal', 'fechaInicial', 'fechaFinal'));
    }

    /**
     * Informe de compras por usuario (modo fichas, tipo 3)
     */
    public function comprasUsuarios(Request $request)
    {
        $ajustes = DB::connection('site')->table('ajustes')->first();
        
        if ($ajustes->modo_operacion === 'mesas') {
            return redirect()->route('informes.index');
        }

        $fechaInicial = $request->input('fecha_inicial', now()->startOfMonth()->format('Y-m-d'));
        $fechaFinal = $request->input('fecha_final', now()->format('Y-m-d'));

        // Obtener compras por usuario desde fichas tipo 3
        $comprasPorUsuario = DB::connection('site')
            ->table('fichas')
            ->whereDate('fichas.fecha', '>=', $fechaInicial)
            ->whereDate('fichas.fecha', '<=', $fechaFinal)
            ->where('fichas.tipo', 3) // Solo fichas de compras
            ->where('fichas.estado', 1) // Estado cerrado
            ->whereNotNull('fichas.user_id')
            ->select(
                'fichas.user_id',
                DB::raw('COUNT(DISTINCT fichas.uuid) as total_compras'),
                DB::raw('SUM(fichas.precio) as total_gastado'),
                DB::raw('AVG(fichas.precio) as ticket_medio')
            )
            ->groupBy('fichas.user_id')
            ->get()
            ->keyBy('user_id');

        // Obtener nombres de usuarios desde la base de datos principal
        $usuarioIds = $comprasPorUsuario->keys()->toArray();
        $usuarios = DB::table('users')
            ->whereIn('id', $usuarioIds)
            ->pluck('name', 'id');

        // Combinar datos
        $comprasUsuarios = $comprasPorUsuario->map(function ($compra) use ($usuarios) {
            return (object) [
                'id' => $compra->user_id,
                'usuario' => $usuarios[$compra->user_id] ?? 'Desconocido',
                'total_compras' => $compra->total_compras,
                'total_gastado' => $compra->total_gastado,
                'ticket_medio' => $compra->ticket_medio
            ];
        })->sortByDesc('total_gastado')->values();

        $totalGeneral = $comprasUsuarios->sum('total_gastado');
        $comprasTotal = $comprasUsuarios->sum('total_compras');

        return view('informes.compras-usuarios', compact('comprasUsuarios', 'totalGeneral', 'comprasTotal', 'fechaInicial', 'fechaFinal'));
    }

    /**
     * Informe de evolución temporal (modo fichas)
     */
    public function evolucionTemporal(Request $request)
    {
        $ajustes = DB::connection('site')->table('ajustes')->first();
        
        if ($ajustes->modo_operacion === 'mesas') {
            return redirect()->route('informes.index');
        }

        $fechaInicial = $request->input('fecha_inicial', now()->subDays(30)->format('Y-m-d'));
        $fechaFinal = $request->input('fecha_final', now()->format('Y-m-d'));

        // Ventas por día desde fichas
        $ventasPorDia = DB::connection('site')
            ->table('fichas')
            ->whereDate('fecha', '>=', $fechaInicial)
            ->whereDate('fecha', '<=', $fechaFinal)
            ->where('tipo','<', 5) // Excluir fichas de mesas (tipo 5) y de tipo 3 (compras)
            ->where('tipo','!=', 3)  
            ->where('estado', 1) // Estado cerrado
            ->select(
                DB::raw('DATE(fecha) as fecha'),
                DB::raw('COUNT(*) as num_transacciones'),
                DB::raw('SUM(precio) as total_vendido'),
                DB::raw('AVG(precio) as ticket_medio')
            )
            ->groupBy(DB::raw('DATE(fecha)'))
            ->orderBy('fecha')
            ->get();

        // Ventas por mes desde fichas
        $ventasPorMes = DB::connection('site')
            ->table('fichas')
            ->whereDate('fecha', '>=', now()->subMonths(12)->format('Y-m-d'))
            ->whereDate('fecha', '<=', now()->format('Y-m-d'))
            ->where('tipo', '<', 5)
            ->where('estado', 1)
            ->select(
                DB::raw('YEAR(fecha) as año'),
                DB::raw('MONTH(fecha) as mes'),
                DB::raw('COUNT(*) as num_transacciones'),
                DB::raw('SUM(precio) as total_vendido'),
                DB::raw('AVG(precio) as ticket_medio')
            )
            ->groupBy(DB::raw('YEAR(fecha)'), DB::raw('MONTH(fecha)'))
            ->orderBy('año')
            ->orderBy('mes')
            ->get();

        // Formatear mes/año en formato compacto MM/YYYY
        $ventasPorMes = $ventasPorMes->map(function($item) {
            $item->mes_nombre = sprintf('%02d/%d', $item->mes, $item->año);
            return $item;
        });

        // Compras por mes desde fichas tipo 3
        $comprasPorMes = DB::connection('site')
            ->table('fichas')
            ->whereDate('fecha', '>=', now()->subMonths(12)->format('Y-m-d'))
            ->whereDate('fecha', '<=', now()->format('Y-m-d'))
            ->where('tipo', 3) // Solo fichas de compras
            ->where('estado', 1)
            ->select(
                DB::raw('YEAR(fecha) as año'),
                DB::raw('MONTH(fecha) as mes'),
                DB::raw('COUNT(*) as num_transacciones'),
                DB::raw('SUM(precio) as total_gastado'),
                DB::raw('AVG(precio) as ticket_medio')
            )
            ->groupBy(DB::raw('YEAR(fecha)'), DB::raw('MONTH(fecha)'))
            ->orderBy('año')
            ->orderBy('mes')
            ->get();

        // Formatear mes/año en formato compacto MM/YYYY
        $comprasPorMes = $comprasPorMes->map(function($item) {
            $item->mes_nombre = sprintf('%02d/%d', $item->mes, $item->año);
            return $item;
        });

        $totalPeriodo = $ventasPorDia->sum('total_vendido');
        $transaccionesTotal = $ventasPorDia->sum('num_transacciones');

        return view('informes.evolucion-temporal', compact('ventasPorDia', 'ventasPorMes', 'comprasPorMes', 'totalPeriodo', 'transaccionesTotal', 'fechaInicial', 'fechaFinal'));
    }
}
