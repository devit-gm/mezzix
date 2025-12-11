<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use App\Models\Albaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProveedoresController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Proveedor::query();

        // Búsqueda
        if ($request->has('buscar') && !empty($request->buscar)) {
            $query->buscar($request->buscar);
        }

        // Filtro por estado
        if ($request->has('estado')) {
            if ($request->estado === 'activo') {
                $query->where('activo', true);
            } elseif ($request->estado === 'inactivo') {
                $query->where('activo', false);
            }
        }

        // Ordenamiento
        $orden = $request->get('orden', 'nombre');
        $direccion = $request->get('direccion', 'asc');
        $query->orderBy($orden, $direccion);

        $proveedores = $query->paginate(15);

        // Estadísticas generales
        $estadisticas = [
            'total' => Proveedor::count(),
            'activos' => Proveedor::where('activo', true)->count(),
            'inactivos' => Proveedor::where('activo', false)->count(),
            'total_compras' => Albaran::where('estado', 'recibido')->sum('total'),
        ];

        return view('proveedores.index', compact('proveedores', 'estadisticas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('proveedores.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'cif' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'nullable|string',
            'ciudad' => 'nullable|string|max:100',
            'codigo_postal' => 'nullable|string|max:10',
            'pais' => 'nullable|string|max:100',
            'contacto_principal' => 'nullable|string|max:255',
            'condiciones_pago' => 'nullable|string',
            'dias_pago' => 'nullable|integer|min:0|max:365',
            'cuenta_bancaria' => 'nullable|string|max:34',
            'notas' => 'nullable|string',
            'activo' => 'boolean',
            'descuento_general' => 'nullable|numeric|min:0|max:100',
        ]);

        $proveedor = Proveedor::create($validated);

        return redirect()
            ->route('proveedores.show', $proveedor->uuid)
            ->with('success', 'Proveedor creado correctamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
        $proveedor = Proveedor::where('uuid', $uuid)->firstOrFail();

        // Cargar albaranes con sus líneas
        $albaranes = $proveedor->albaranes()
            ->with('lineas')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Estadísticas del proveedor
        $estadisticas = [
            'total_albaranes' => $proveedor->albaranes()->count(),
            'albaranes_pendientes' => $proveedor->albaranesPendientes()->count(),
            'total_compras' => $proveedor->albaranes()->where('estado', 'recibido')->sum('total'),
            'compra_media' => $proveedor->albaranes()->where('estado', 'recibido')->avg('total'),
            'ultimo_albaran' => $proveedor->ultimoAlbaran(),
        ];

        // Compras por mes (últimos 12 meses)
        $comprasPorMes = $proveedor->albaranes()
            ->where('estado', 'recibido')
            ->where('created_at', '>=', now()->subMonths(12))
            ->select(
                DB::raw('YEAR(created_at) as año'),
                DB::raw('MONTH(created_at) as mes'),
                DB::raw('SUM(total) as total')
            )
            ->groupBy('año', 'mes')
            ->orderBy('año', 'desc')
            ->orderBy('mes', 'desc')
            ->get();

        return view('proveedores.show', compact('proveedor', 'albaranes', 'estadisticas', 'comprasPorMes'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $uuid)
    {
        $proveedor = Proveedor::where('uuid', $uuid)->firstOrFail();
        return view('proveedores.edit', compact('proveedor'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $uuid)
    {
        $proveedor = Proveedor::where('uuid', $uuid)->firstOrFail();

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'cif' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'nullable|string',
            'ciudad' => 'nullable|string|max:100',
            'codigo_postal' => 'nullable|string|max:10',
            'pais' => 'nullable|string|max:100',
            'contacto_principal' => 'nullable|string|max:255',
            'condiciones_pago' => 'nullable|string',
            'dias_pago' => 'nullable|integer|min:0|max:365',
            'cuenta_bancaria' => 'nullable|string|max:34',
            'notas' => 'nullable|string',
            'activo' => 'boolean',
            'descuento_general' => 'nullable|numeric|min:0|max:100',
        ]);

        $proveedor->update($validated);

        return redirect()
            ->route('proveedores.show', $proveedor->uuid)
            ->with('success', 'Proveedor actualizado correctamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
    {
        $proveedor = Proveedor::where('uuid', $uuid)->firstOrFail();

        // Verificar si tiene albaranes asociados
        if ($proveedor->albaranes()->count() > 0) {
            return redirect()
                ->route('proveedores.index')
                ->with('error', 'No se puede eliminar el proveedor porque tiene albaranes asociados');
        }

        $proveedor->delete();

        return redirect()
            ->route('proveedores.index')
            ->with('success', 'Proveedor eliminado correctamente');
    }

    /**
     * Toggle active status
     */
    public function toggleActivo(string $uuid)
    {
        $proveedor = Proveedor::where('uuid', $uuid)->firstOrFail();
        $proveedor->activo = !$proveedor->activo;
        $proveedor->save();

        $estado = $proveedor->activo ? 'activado' : 'desactivado';

        return redirect()
            ->back()
            ->with('success', "Proveedor {$estado} correctamente");
    }

    /**
     * Obtener estadísticas de compras por proveedor
     */
    public function estadisticas(Request $request)
    {
        $fechaInicio = $request->get('fecha_inicio', now()->subMonths(12)->startOfMonth());
        $fechaFin = $request->get('fecha_fin', now()->endOfMonth());

        $proveedores = Proveedor::withCount(['albaranes as total_compras' => function ($query) use ($fechaInicio, $fechaFin) {
            $query->where('estado', 'recibido')
                  ->whereBetween('created_at', [$fechaInicio, $fechaFin])
                  ->select(DB::raw('SUM(total)'));
        }])
        ->having('total_compras', '>', 0)
        ->orderBy('total_compras', 'desc')
        ->limit(10)
        ->get();

        return view('proveedores.estadisticas', compact('proveedores', 'fechaInicio', 'fechaFin'));
    }
}
