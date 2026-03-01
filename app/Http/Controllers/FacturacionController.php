<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Recibo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FacturacionController extends Controller
{
    public function index(Request $request)
    {
        $site = get_site();
        $ajustes = \App\Models\Ajustes::first();
        $modoOperacion = $ajustes->modo_operacion ?? 'fichas';
        
        // Obtener el año seleccionado o el año actual por defecto
        $año = $request->input('año', date('Y'));
        
        // Si estamos en modo mesas, mostrar facturación por camarero
        if ($modoOperacion === 'mesas') {
            // Obtener meses seleccionado o todos
            $mes = $request->input('mes', null);
            
            // Obtener años y meses disponibles
            $añosDisponibles = DB::connection('site')
                ->table('fichas')
                ->selectRaw('DISTINCT YEAR(fecha) as año')
                ->whereNotNull('fecha')
                ->orderBy('año', 'desc')
                ->pluck('año')
                ->toArray();
            
            if (empty($añosDisponibles)) {
                $añosDisponibles = [date('Y')];
            }
            
            if (!in_array($año, $añosDisponibles)) {
                $año = $añosDisponibles[0];
            }
            
            // Obtener facturación por camarero y mes desde fichas_recibos
            $fichasData = DB::connection('site')
                ->table('fichas_recibos')
                ->selectRaw('user_id as camarero_id, MONTH(fecha) as mes, SUM(precio) as total')
                ->whereYear('fecha', $año)
                ->where('tipo', 1) // Tipo 1 = ingresos/ventas
                ->where('estado', 1) // Estado 1 = pagado
                ->whereNotNull('user_id')
                ->groupBy('user_id', 'mes')
                ->get();
            
            // Obtenemos los usuarios de la base de datos principal
            $usuarios = \App\Models\User::whereIn('id', $fichasData->pluck('camarero_id')->unique())->get()->keyBy('id');
            
            // Combinamos los datos
            $query = $fichasData->map(function($item) use ($usuarios) {
                return (object)[
                    'camarero' => $usuarios[$item->camarero_id]->name ?? 'Desconocido',
                    'mes' => $item->mes,
                    'total' => $item->total
                ];
            });
            
            // Organizar datos por camarero
            $datosGraficoCamareros = [];
            $camareros = [];
            
            foreach ($query as $row) {
                $camarero = $row->camarero;
                $mes = (int)$row->mes;
                $total = (float)$row->total;
                
                if (!isset($datosGraficoCamareros[$camarero])) {
                    $datosGraficoCamareros[$camarero] = array_fill(1, 12, 0);
                    $camareros[] = $camarero;
                }
                
                $datosGraficoCamareros[$camarero][$mes] = $total;
            }
            
            // Convertir a formato para Chart.js
            $datasets = [];
            $colores = [
                'rgba(220, 53, 69, 0.7)',
                'rgba(13, 110, 253, 0.7)',
                'rgba(25, 135, 84, 0.7)',
                'rgba(255, 193, 7, 0.7)',
                'rgba(111, 66, 193, 0.7)',
                'rgba(13, 202, 240, 0.7)',
            ];
            
            $i = 0;
            foreach ($datosGraficoCamareros as $camarero => $meses) {
                $datasets[] = [
                    'label' => $camarero,
                    'data' => array_values($meses),
                    'backgroundColor' => $colores[$i % count($colores)],
                    'borderColor' => str_replace('0.7', '1', $colores[$i % count($colores)]),
                    'borderWidth' => 2,
                ];
                $i++;
            }
            
            // Calcular totales por camarero
            $totalesCamareros = [];
            foreach ($datosGraficoCamareros as $camarero => $meses) {
                $totalesCamareros[$camarero] = array_sum($meses);
            }
            
            // Calcular desglose de IVA desde mesa_historial
            $desgloseIva = [];
            $totalBaseImponible = 0;
            $totalCuotaIva = 0;
            
            // Obtener datos históricos de mesas liberadas con desglose IVA
            $historialMesas = DB::connection('site')
                ->table('mesa_historial')
                ->whereYear('fecha_accion', $año)
                ->where('accion', 'liberar')
                ->whereNotNull('detalles')
                ->get();
            
            foreach ($historialMesas as $registro) {
                $detalles = json_decode($registro->detalles, true);
                
                if (isset($detalles['iva_desglose']) && is_array($detalles['iva_desglose'])) {
                    foreach ($detalles['iva_desglose'] as $tipoIva => $datos) {
                        $porcentaje = floatval($tipoIva);
                        
                        if (!isset($desgloseIva[$porcentaje])) {
                            $desgloseIva[$porcentaje] = [
                                'base' => 0,
                                'cuota' => 0
                            ];
                        }
                        
                        $desgloseIva[$porcentaje]['base'] += floatval($datos['base'] ?? 0);
                        $desgloseIva[$porcentaje]['cuota'] += floatval($datos['cuota'] ?? 0);
                    }
                }
            }
            
            // Calcular totales de IVA
            foreach ($desgloseIva as $datos) {
                $totalBaseImponible += $datos['base'];
                $totalCuotaIva += $datos['cuota'];
            }
            
            // Ordenar por porcentaje de IVA
            ksort($desgloseIva);
            
            return view('facturacion.index-mesas', compact('datasets', 'año', 'añosDisponibles', 'totalesCamareros', 'modoOperacion', 'desgloseIva', 'totalBaseImponible', 'totalCuotaIva'));
        }
        
        // Modo fichas: comportamiento original
        // Obtener años disponibles en los datos
        $añosDisponibles = Recibo::selectRaw('DISTINCT YEAR(fecha) as año')
            ->orderBy('año', 'desc')
            ->pluck('año')
            ->toArray();
        
        // Si no hay años disponibles, usar el año actual
        if (empty($añosDisponibles)) {
            $añosDisponibles = [date('Y')];
        }
        
        // Validar que el año seleccionado esté en los años disponibles
        if (!in_array($año, $añosDisponibles)) {
            $año = $añosDisponibles[0];
        }
        
        // Obtener ventas por mes (tipo 1 = gastos/ventas)
        $ventasTipo1 = Recibo::selectRaw('MONTH(fecha) as mes, SUM(precio) as total')
            ->whereYear('fecha', $año)
            ->where('tipo', 1)
            ->where('estado', 1)
            ->groupBy('mes')
            ->orderBy('mes')
            ->get()
            ->pluck('total', 'mes')
            ->toArray();
        
        // Obtener compras/ingresos por mes (tipo 2)
        $ventasTipo2 = Recibo::selectRaw('MONTH(fecha) as mes, SUM(precio) as total')
            ->whereYear('fecha', $año)
            ->where('tipo', 2)
            ->where('estado', 1)
            ->groupBy('mes')
            ->orderBy('mes')
            ->get()
            ->pluck('total', 'mes')
            ->toArray();
        
        // Inicializar todos los meses con 0
        $datosGrafico = array_fill(1, 12, 0);
        
        // Calcular el balance (tipo 1 - tipo 2) para cada mes
        for ($mes = 1; $mes <= 12; $mes++) {
            $tipo1 = isset($ventasTipo1[$mes]) ? (float) $ventasTipo1[$mes] : 0;
            $tipo2 = isset($ventasTipo2[$mes]) ? (float) $ventasTipo2[$mes] : 0;
            $datosGrafico[$mes] = $tipo1 - $tipo2;
        }
        
        // Convertir a array indexado
        $datosGrafico = array_values($datosGrafico);
        
        // Calcular totales anuales para el gráfico de sectores
        $totalIngresos = array_sum($ventasTipo1);
        $totalGastos = array_sum($ventasTipo2);
        
        return view('facturacion.index', compact('datosGrafico', 'año', 'añosDisponibles', 'totalIngresos', 'totalGastos', 'modoOperacion'));
    }
}
