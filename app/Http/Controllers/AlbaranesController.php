<?php

namespace App\Http\Controllers;

use App\Models\Albaran;
use App\Models\AlbaranLinea;
use App\Models\Producto;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AlbaranesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Albaran::with('lineas', 'usuario', 'proveedor');

        // Filtrar por estado
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // Filtrar por proveedor
        if ($request->filled('proveedor_id')) {
            $query->where('proveedor_id', $request->proveedor_id);
        }

        // Filtrar por fecha
        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha', '<=', $request->fecha_hasta);
        }

        $albaranes = $query->orderBy('fecha', 'desc')->paginate(20);
        $proveedores = Proveedor::activo()->orderBy('nombre')->get();

        return view('albaranes.index', compact('albaranes', 'proveedores'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        try {
            $productos = Producto::with('familiaObj')->orderBy('nombre')->get();
            $proveedores = Proveedor::activo()->orderBy('nombre')->get();
        } catch (\Exception $e) {
            \Log::error('Error al cargar datos: ' . $e->getMessage());
            $productos = collect([]);
            $proveedores = collect([]);
        }
        return view('albaranes.create', compact('productos', 'proveedores'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'proveedor_id' => 'required|exists:site.proveedores,id',
            'numero_albaran' => 'required|string|max:255',
            'fecha_albaran' => 'nullable|date',
            'fecha' => 'required|date',
            'observaciones' => 'nullable|string',
            'lineas' => 'required|array|min:1',
            'lineas.*.producto_id' => 'required|string|size:36',
            'lineas.*.cantidad' => 'required|numeric|min:0.01',
            'lineas.*.precio_coste' => 'required|numeric|min:0',
        ]);

        // Verificar unicidad del número de albarán en la conexión site
        $existeAlbaran = Albaran::where('numero_albaran', $request->numero_albaran)->exists();
        if ($existeAlbaran) {
            return back()->withInput()->withErrors([
                'numero_albaran' => 'El número de albarán ya existe.'
            ]);
        }

        DB::beginTransaction();
        try {
            // Crear el albarán
            $albaran = Albaran::create([
                'proveedor_id' => $request->proveedor_id,
                'numero_albaran' => $request->numero_albaran,
                'fecha_albaran' => $request->fecha_albaran,
                'fecha' => $request->fecha,
                'estado' => 'pendiente',
                'observaciones' => $request->observaciones,
                'usuario_id' => Auth::id(),
            ]);

            // Crear las líneas
            foreach ($request->lineas as $lineaData) {
                AlbaranLinea::create([
                    'albaran_id' => $albaran->id,
                    'producto_id' => $lineaData['producto_id'],
                    'cantidad' => $lineaData['cantidad'],
                    'precio_coste' => $lineaData['precio_coste'],
                ]);
            }

            DB::commit();

            return redirect()->route('albaranes.show', $albaran->id)
                ->with('success', 'Albarán creado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Error al crear el albarán: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $albaran = Albaran::with('lineas.producto.familiaObj', 'usuario')->findOrFail($id);
        return view('albaranes.show', compact('albaran'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $albaran = Albaran::with('lineas.producto.familiaObj')->findOrFail($id);
        
        // Solo se pueden editar albaranes pendientes
        if ($albaran->estado !== 'pendiente') {
            return redirect()->route('albaranes.show', $albaran->id)
                ->with('error', 'Solo se pueden editar albaranes en estado pendiente.');
        }

        $productos = Producto::with('familiaObj')->orderBy('nombre')->get();
        $proveedores = Proveedor::activo()->orderBy('nombre')->get();
        return view('albaranes.edit', compact('albaran', 'productos', 'proveedores'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $albaran = Albaran::findOrFail($id);

        // Solo se pueden editar albaranes pendientes
        if ($albaran->estado !== 'pendiente') {
            return redirect()->route('albaranes.show', $albaran->id)
                ->with('error', 'Solo se pueden editar albaranes en estado pendiente.');
        }

        $request->validate([
            'proveedor_id' => 'required|exists:site.proveedores,id',
            'numero_albaran' => 'required|string|max:255',
            'fecha_albaran' => 'nullable|date',
            'fecha' => 'required|date',
            'observaciones' => 'nullable|string',
            'lineas' => 'required|array|min:1',
            'lineas.*.producto_id' => 'required|string|size:36',
            'lineas.*.cantidad' => 'required|numeric|min:0.01',
            'lineas.*.precio_coste' => 'required|numeric|min:0',
        ]);

        // Verificar unicidad del número de albarán (excepto el actual) en la conexión site
        $existeAlbaran = Albaran::where('numero_albaran', $request->numero_albaran)
            ->where('id', '!=', $albaran->id)
            ->exists();
        if ($existeAlbaran) {
            return back()->withInput()->withErrors([
                'numero_albaran' => 'El número de albarán ya existe.'
            ]);
        }

        DB::beginTransaction();
        try {
            // Actualizar el albarán
            $albaran->update([
                'proveedor_id' => $request->proveedor_id,
                'numero_albaran' => $request->numero_albaran,
                'fecha_albaran' => $request->fecha_albaran,
                'fecha' => $request->fecha,
                'observaciones' => $request->observaciones,
            ]);

            // Eliminar líneas existentes
            $albaran->lineas()->delete();

            // Crear nuevas líneas
            foreach ($request->lineas as $lineaData) {
                AlbaranLinea::create([
                    'albaran_id' => $albaran->id,
                    'producto_id' => $lineaData['producto_id'],
                    'cantidad' => $lineaData['cantidad'],
                    'precio_coste' => $lineaData['precio_coste'],
                ]);
            }

            DB::commit();

            return redirect()->route('albaranes.show', $albaran->id)
                ->with('success', 'Albarán actualizado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Error al actualizar el albarán: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $albaran = Albaran::findOrFail($id);

        // Solo se pueden eliminar albaranes pendientes
        if ($albaran->estado !== 'pendiente') {
            return back()->with('error', 'Solo se pueden eliminar albaranes en estado pendiente.');
        }

        try {
            $albaran->delete();
            return redirect()->route('albaranes.index')
                ->with('success', 'Albarán eliminado correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al eliminar el albarán: ' . $e->getMessage());
        }
    }

    /**
     * Confirmar la recepción del albarán y actualizar stock
     */
    public function confirmarRecepcion($id)
    {
        $albaran = Albaran::with('lineas.producto')->findOrFail($id);

        try {
            if ($albaran->confirmarRecepcion()) {
                return back()->with('success', 'Albarán recibido y stock actualizado correctamente.');
            } else {
                return back()->with('error', 'El albarán ya ha sido recibido previamente.');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Error al confirmar la recepción: ' . $e->getMessage());
        }
    }

    /**
     * Generar PDF del albarán
     */
    public function pdf($id)
    {
        $albaran = Albaran::with('lineas.producto.familiaObj', 'usuario')->findOrFail($id);

        // Solo se puede descargar PDF de albaranes recibidos o facturados
        if ($albaran->estado === 'pendiente') {
            return back()->with('error', 'Solo se puede descargar el PDF de albaranes recibidos.');
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('albaranes.pdf', compact('albaran'))
            ->setPaper('a4', 'portrait');
        
        $nombreArchivo = 'albaran_' . str_replace('/', '_', $albaran->numero_albaran) . '_' . $albaran->fecha->format('Y-m-d') . '.pdf';
        
        return $pdf->download($nombreArchivo);
    }
}
