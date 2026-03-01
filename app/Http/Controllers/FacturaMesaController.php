<?php

namespace App\Http\Controllers;

use App\Models\FacturaMesa;
use App\Models\Ficha;
use App\Models\FichaProducto;
use App\Models\FichaServicio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class FacturaMesaController extends Controller
{
    /**
     * Listado de facturas emitidas
     */
    public function index(Request $request)
    {
        $fechaInicial = $request->input('fecha_inicial', now()->startOfMonth()->format('Y-m-d'));
        $fechaFinal = $request->input('fecha_final', now()->format('Y-m-d'));
        
        $facturas = FacturaMesa::with(['mesa', 'camarero'])
            ->whereDate('fecha', '>=', $fechaInicial)
            ->whereDate('fecha', '<=', $fechaFinal)
            ->orderBy('fecha', 'desc')
            ->orderBy('numero_factura', 'desc')
            ->paginate(20);
        
        // Calcular totales
        $totalFacturas = FacturaMesa::whereDate('fecha', '>=', $fechaInicial)
            ->whereDate('fecha', '<=', $fechaFinal)
            ->count();
            
        $totalSubtotal = FacturaMesa::whereDate('fecha', '>=', $fechaInicial)
            ->whereDate('fecha', '<=', $fechaFinal)
            ->sum('subtotal');
            
        $totalIva = FacturaMesa::whereDate('fecha', '>=', $fechaInicial)
            ->whereDate('fecha', '<=', $fechaFinal)
            ->sum('total_iva');
            
        $totalImporte = FacturaMesa::whereDate('fecha', '>=', $fechaInicial)
            ->whereDate('fecha', '<=', $fechaFinal)
            ->sum('total');
        
        return view('facturas.index', compact(
            'facturas', 
            'fechaInicial', 
            'fechaFinal', 
            'totalFacturas', 
            'totalSubtotal', 
            'totalIva', 
            'totalImporte'
        ));
    }
    
    /**
     * Mostrar formulario para crear factura
     */
    public function crear($mesaId)
    {
        $mesa = Ficha::with(['productos.producto', 'servicios.servicio'])->findOrFail($mesaId);
        
        // Verificar que la mesa esté cerrada
        if ($mesa->estado_mesa !== 'cerrada') {
            return redirect()->back()->with('error', 'La mesa debe estar cerrada para generar una factura');
        }
        
        // Calcular totales con IVA
        $lineas = [];
        $subtotal = 0;
        $totalIva = 0;
        
        // Añadir productos
        foreach ($mesa->productos as $fp) {
            if ($fp->producto) {
                $iva = $fp->producto->iva ?? 21;
                $pvp = $fp->cantidad * $fp->precio; // El precio ya incluye IVA
                $baseImponible = $pvp / (1 + $iva / 100);
                $importeIva = $pvp - $baseImponible;
                
                $lineas[] = [
                    'tipo' => 'producto',
                    'nombre' => $fp->producto->nombre,
                    'cantidad' => $fp->cantidad,
                    'precio' => $fp->precio,
                    'iva' => $iva,
                    'base_imponible' => $baseImponible,
                    'importe_iva' => $importeIva,
                    'total' => $pvp
                ];
                
                $subtotal += $baseImponible;
                $totalIva += $importeIva;
            }
        }
        
        // Añadir servicios
        foreach ($mesa->servicios as $fs) {
            if ($fs->servicio) {
                $iva = $fs->servicio->iva ?? 21;
                $pvp = $fs->precio; // El precio ya incluye IVA, no hay cantidad en servicios
                $baseImponible = $pvp / (1 + $iva / 100);
                $importeIva = $pvp - $baseImponible;
                
                $lineas[] = [
                    'tipo' => 'servicio',
                    'nombre' => $fs->servicio->nombre,
                    'cantidad' => 1,
                    'precio' => $fs->precio,
                    'iva' => $iva,
                    'base_imponible' => $baseImponible,
                    'importe_iva' => $importeIva,
                    'total' => $pvp
                ];
                
                $subtotal += $baseImponible;
                $totalIva += $importeIva;
            }
        }
        
        $total = $subtotal + $totalIva;
        
        // Calcular desglose de IVA
        $ivaDesglose = [];
        foreach ($lineas as $linea) {
            $ivaKey = number_format($linea['iva'], 2);
            if (!isset($ivaDesglose[$ivaKey])) {
                $ivaDesglose[$ivaKey] = [
                    'porcentaje' => $linea['iva'],
                    'base' => 0,
                    'cuota' => 0
                ];
            }
            $ivaDesglose[$ivaKey]['base'] += $linea['base_imponible'];
            $ivaDesglose[$ivaKey]['cuota'] += $linea['importe_iva'];
        }
        
        return view('facturas.crear', compact('mesa', 'lineas', 'subtotal', 'totalIva', 'total', 'ivaDesglose'));
    }
    
    /**
     * Guardar nueva factura
     */
    public function store(Request $request, $mesaId)
    {
        $request->validate([
            'cliente_nombre' => 'nullable|string|max:255',
            'cliente_nif' => 'nullable|string|max:20'
        ]);
        
        $mesa = Ficha::with(['productos.producto', 'servicios.servicio'])->findOrFail($mesaId);
        
        // Verificar que la mesa esté cerrada
        if ($mesa->estado_mesa !== 'cerrada') {
            return redirect()->back()->with('error', 'La mesa debe estar cerrada para generar una factura');
        }
        
        // Verificar que no exista ya una factura para esta mesa
        $facturaExistente = FacturaMesa::where('mesa_id', $mesa->uuid)->first();
        if ($facturaExistente) {
            return redirect()->route('facturas.show', $facturaExistente->id)
                ->with('warning', 'Ya existe una factura para esta mesa');
        }
        
        // Calcular totales y líneas
        $lineas = [];
        $subtotal = 0;
        $totalIva = 0;
        
        foreach ($mesa->productos as $fp) {
            if ($fp->producto) {
                $iva = $fp->producto->iva ?? 21;
                $pvp = $fp->cantidad * $fp->precio; // El precio ya incluye IVA
                $baseImponible = $pvp / (1 + $iva / 100);
                $importeIva = $pvp - $baseImponible;
                
                $lineas[] = [
                    'tipo' => 'producto',
                    'nombre' => $fp->producto->nombre,
                    'cantidad' => $fp->cantidad,
                    'precio' => $fp->precio,
                    'iva' => $iva,
                    'base_imponible' => round($baseImponible, 2),
                    'importe_iva' => round($importeIva, 2),
                    'total' => round($pvp, 2)
                ];
                
                $subtotal += $baseImponible;
                $totalIva += $importeIva;
            }
        }
        
        foreach ($mesa->servicios as $fs) {
            if ($fs->servicio) {
                $iva = $fs->servicio->iva ?? 21;
                $pvp = $fs->precio; // El precio ya incluye IVA, no hay cantidad en servicios
                $baseImponible = $pvp / (1 + $iva / 100);
                $importeIva = $pvp - $baseImponible;
                
                $lineas[] = [
                    'tipo' => 'servicio',
                    'nombre' => $fs->servicio->nombre,
                    'cantidad' => 1,
                    'precio' => $fs->precio,
                    'iva' => $iva,
                    'base_imponible' => round($baseImponible, 2),
                    'importe_iva' => round($importeIva, 2),
                    'total' => round($pvp, 2)
                ];
                
                $subtotal += $baseImponible;
                $totalIva += $importeIva;
            }
        }
        
        // Crear factura
        $factura = FacturaMesa::create([
            'numero_factura' => FacturaMesa::generarNumeroFactura(),
            'fecha' => now(),
            'mesa_id' => $mesa->uuid,
            'camarero_id' => $mesa->camarero_id ?? Auth::id(),
            'cliente_nombre' => $request->cliente_nombre,
            'cliente_nif' => $request->cliente_nif,
            'subtotal' => round($subtotal, 2),
            'total_iva' => round($totalIva, 2),
            'total' => round($subtotal + $totalIva, 2),
            'detalles' => [
                'lineas' => $lineas,
                'mesa_numero' => $mesa->numero_mesa,
                'mesa_descripcion' => $mesa->descripcion
            ]
        ]);
        
        return redirect()->route('facturas.show', $factura->id)
            ->with('success', 'Factura generada correctamente');
    }
    
    /**
     * Ver detalle de factura
     */
    public function show($id)
    {
        $factura = FacturaMesa::with(['mesa', 'camarero'])->findOrFail($id);
        $ivaDesglose = $factura->getDesgloseIva();
        
        return view('facturas.show', compact('factura', 'ivaDesglose'));
    }
    
    /**
     * Descargar PDF de la factura
     */
    public function pdf($id)
    {
        $factura = FacturaMesa::with(['mesa', 'camarero'])->findOrFail($id);
        $ivaDesglose = $factura->getDesgloseIva();
        $ajustes = \App\Models\Ajustes::first();
        $site = get_site();
        
        $pdf = Pdf::loadView('facturas.pdf', compact('factura', 'ivaDesglose', 'ajustes', 'site'));
        
        $nombreArchivo = 'factura-' . str_replace('/', '-', $factura->numero_factura) . '.pdf';
        return $pdf->download($nombreArchivo);
    }
}
