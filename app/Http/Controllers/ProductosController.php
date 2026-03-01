<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Familia;
use App\Models\Producto;
use App\Models\ComposicionProducto;
use Illuminate\Console\View\Components\Component;
use Illuminate\Support\Facades\File;
use Ramsey\Uuid\Uuid;
use App\Models\FichaProducto;
use App\Services\ImageService;
use App\Models\Site;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductosController extends Controller
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
    public function index()
    {
        $productos = productos_menu();
        $ajustes = \App\Models\Ajustes::first();
        
        // Mantener lógica de borrable y precio
        foreach ($productos as $producto) {
            // Reemplazar familia con su relación real si existe la relación cargada
            if (isset($producto->familiaObj)) {
                $producto->familia = $producto->familiaObj;
            }
            if ($producto->combinado == 1) {
                $precio = method_exists($producto, 'componentes') ? $producto->componentes->sum('precio') : 0;
                $producto->precio = number_format((float)$precio, 2, '.', '');
                if (Auth::check() && Auth::user()->role_id == 1 && isset($producto->fichas)) {
                    $tienePendientes = $producto->fichas->contains(fn($f) => $f->estado == 0);
                    $producto->borrable = !$tienePendientes;
                } else {
                    $producto->borrable = false;
                }
            } else {
                $producto->precio = number_format((float)$producto->precio, 2, '.', '');
                $producto->borrable = true;
            }
        }
        return view('productos.index', compact('productos', 'ajustes'));

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|max:255',
            'imagen' => 'required|image|mimes:png,jpg,jpeg,webp|max:2048',
            'posicion' => 'required',
            'familia' => 'required',
            'combinado' => 'required',
            'precio' => 'required',
            'iva' => 'nullable|numeric|min:0|max:100'
        ]);
        
        // Procesar y redimensionar imagen
        $imageName = ImageService::processAndSave($request->imagen, 'public/images');
        
        // Copiar a public/images para compatibilidad
        $sourcePath = storage_path('app/public/images/' . $imageName);
        $destPath = public_path('images/' . $imageName);
        copy($sourcePath, $destPath);

        Producto::create([
            'uuid' => (string) Uuid::uuid4(),
            'nombre' => $request->nombre,
            'imagen' => $imageName,
            'posicion' => $request->posicion,
            'familia' => $request->familia,
            'combinado' => $request->combinado,
            'precio' => $request->precio,
            'ean13' => $request->ean13,
            'iva' => $request->iva ?? 21
        ]);
        
        // 🚀 OPTIMIZACIÓN: Invalidar caché de productos
        \Cache::forget("productos_familia_{$request->familia}");
        \Cache::forget('productos_menu');
        
        return redirect()->route('productos.index')
            ->with('success', __('Producto creado con éxito.'));
    }

    // /**
    //  * Display the specified resource.
    //  */
    // public function show(string $id)
    // {
    //     $producto = Producto::with('fichas')->find($id);
    //     if (Auth::check() && Auth::user()->role_id == 1) {
    //         $producto->borrable = true;
    //         foreach ($producto->fichas as $ficha) {
    //             if ($ficha->estado == 0) {
    //                 $producto->borrable = false;
    //                 break;
    //             }
    //         }
    //     } else {
    //         $producto->borrable = false;
    //     }
    //     return view('productos.show', compact('producto'));
    // }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'nombre' => 'required|max:255',
            'imagen' => 'image|mimes:png,jpg,jpeg,webp|max:2048',
            'posicion' => 'required',
            'familia' => 'required',
            'combinado' => 'required',
            'precio' => 'required',
            'iva' => 'nullable|numeric|min:0|max:100'
        ]);
        $producto = Producto::find($id);

        if ($request->imagen != null) {
            if (File::exists(public_path('images') . '/'  . $producto->imagen)) {
                File::delete(public_path('images') . '/'  . $producto->imagen);
            }
            if (File::exists(storage_path('app/public/images') . '/'  . $producto->imagen)) {
                File::delete(storage_path('app/public/images') . '/'  . $producto->imagen);
            }
            
            // Procesar y redimensionar imagen
            $imageName = ImageService::processAndSave($request->imagen, 'public/images');
            
            // Copiar a public/images para compatibilidad
            $sourcePath = storage_path('app/public/images/' . $imageName);
            $destPath = public_path('images/' . $imageName);
            copy($sourcePath, $destPath);
        } else {
            $imageName = $producto->imagen;
        }

        $producto->update([
            'nombre' => $request->nombre,
            'imagen' => $imageName,
            'posicion' => $request->posicion,
            'familia' => $request->familia,
            'combinado' => $request->combinado,
            'precio' => $request->precio,
            'ean13' => $request->ean13,
            'iva' => $request->iva ?? 21
        ]);
        
        // 🚀 OPTIMIZACIÓN: Invalidar caché de productos (familia actual y anterior)
        \Cache::forget("productos_familia_{$request->familia}");
        \Cache::forget("productos_familia_{$producto->getOriginal('familia')}");
        \Cache::forget('productos_menu');
        
        return redirect()->route('productos.index')
            ->with('success', __('Producto actualizado con éxito.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $producto = Producto::find($id);
        $familiaId = $producto->familia; // Guardar antes de borrar
        
        if (File::exists(public_path('images') . '/'  . $producto->imagen)) {
            File::delete(public_path('images') . '/'  . $producto->imagen);
        }
        ComposicionProducto::where('id_producto', $id)->delete();
        $producto->delete();
        
        // 🚀 OPTIMIZACIÓN: Invalidar caché de productos
        \Cache::forget("productos_familia_{$familiaId}");
        \Cache::forget('productos_menu');
        
        return redirect()->route('productos.index')
            ->with('success', __('Producto eliminado con éxito'));
    }

    /**
     * Show the form for creating a new post.
     */
    public function create()
    {
        $familias = Familia::orderBy('nombre')->get();
        $ajustes = DB::connection('site')->table('ajustes')->first();
        return view('productos.create', compact('familias', 'ajustes'));
    }

    /**
     * Show the form for editing the specified post.
     *
     * @param  int  $id
     */
    public function edit($id)
    {
        $producto = Producto::with(['fichas', 'familiaObj'])->findOrFail($id);
        //Find components of product in composition table
        $composicion = ComposicionProducto::with('componenteProducto')
            ->where('id_producto', $id)
            ->get();
        if ($producto->combinado == 1) {
            $precio = 0.00;
            foreach ($composicion as $componente) {
                if ($componente->componenteProducto) {
                    $precio += $componente->componenteProducto->precio;
                }
            }
            $producto->precio =  number_format((float)$precio, 2, '.', '');
        }

        if (Auth::check() && Auth::user()->role_id == 1) {
            $producto->borrable = true;
            $producto->fichas = FichaProducto::where('id_producto', $producto->id)->get();
            foreach ($producto->fichas as $ficha) {
                if ($ficha->estado == 0) {
                    $producto->borrable = false;
                    break;
                }
            }
        } else {
            $producto->borrable = false;
        }
        $familias = Familia::orderBy('nombre')->get();
        $ajustes = DB::connection('site')->table('ajustes')->first();
        return view('productos.edit', compact('producto', 'familias', 'ajustes'));
    }

    public function components($id)
    {
        $producto = Producto::with('familiaObj')->findOrFail($id);
        //Find components of product in composition table
        $composicion = ComposicionProducto::with('componenteProducto')
            ->where('id_producto', $id)
            ->get();
        if ($producto->combinado == 1) {
            $precio = 0.00;
            foreach ($composicion as $componente) {
                if ($componente->componenteProducto) {
                    $precio += $componente->componenteProducto->precio;
                }
            }
            $producto->precio =  number_format((float)$precio, 2, '.', '');
        }
        $familias = Familia::orderBy('posicion')->get();
        $producto->familia = $producto->familiaObj;

        // Obtener componentes del producto en una consulta
        $componentesActuales = ComposicionProducto::where('id_producto', $id)
            ->pluck('id_componente')
            ->toArray();
        
        $componentes = Producto::where('combinado', 0)->orderBy('nombre')->get();
        foreach ($componentes as $componente) {
            $componente->familia = in_array($componente->uuid, $componentesActuales) ? 1 : 0;
        }
        return view('productos.components', compact('producto', 'familias', 'componentes'));
    }

    public function update_components(Request $request, string $id)
    {
        ComposicionProducto::where('id_producto', $id)->delete();
        foreach ($request->componentes as $componente) {
            ComposicionProducto::create([
                'uuid' => (string) Uuid::uuid4(),
                'id_producto' => $id,
                'id_componente' => $componente
            ]);
        }

        $producto = Producto::with('familiaObj')->findOrFail($id);
        //Find components of product in composition table
        $composicion = ComposicionProducto::with('componenteProducto')
            ->where('id_producto', $id)
            ->get();
        if ($producto->combinado == 1) {
            $precio = 0.00;
            foreach ($composicion as $componente) {
                if ($componente->componenteProducto) {
                    $precio += $componente->componenteProducto->precio;
                }
            }
            $producto->precio =  number_format((float)$precio, 2, '.', '');
        }
        $familias = Familia::orderBy('posicion')->get();
        $producto->familia = $producto->familiaObj;

        // Obtener componentes del producto en una consulta
        $componentesActuales = ComposicionProducto::where('id_producto', $id)
            ->pluck('id_componente')
            ->toArray();
        
        $componentes = Producto::where('combinado', 0)->orderBy('nombre')->get();
        foreach ($componentes as $componente) {
            $componente->familia = in_array($componente->uuid, $componentesActuales) ? 1 : 0;
        }
        $success = new \Illuminate\Support\MessageBag();
        $success->add('msg', 'Componentes actualizados con éxito.');

        return view('productos.components', compact('producto', 'familias', 'componentes',));
    }

    public function inventory(Request $request)
    {
        if ($request->isMethod('put')) {
            $productos = $request->stock;
            $uuids = $request->uuid;
            $stockService = new \App\Services\StockNotificationService();
            
            foreach ($uuids as $uuid) {
                $stockAnterior = Producto::where('uuid', $uuid)->value('stock');
                
                Producto::where('uuid', $uuid)->update([
                    'stock' =>  $productos[$uuid]
                ]);
                
                // Si el stock ha disminuido, verificar si hay que notificar
                if ($productos[$uuid] < $stockAnterior) {
                    $stockService->verificarYNotificar($uuid);
                }
            }

            return redirect()->route('productos.inventory')
                ->with('success', __('Inventario actualizado con éxito.'));
        }
        $productos = Producto::where('combinado', 0)->orderBy('nombre')->get();
        $ajustes = \App\Models\Ajustes::first();
        return view('productos.inventory', compact('productos', 'ajustes'));
    }

    public function buscarPorBarcode(Request $request)
    {
        $request->validate([
            'ean13' => 'required|string|max:50'
        ]);

        $ean13 = $request->input('ean13');

        // Buscar producto por código EAN13
        $producto = Producto::where('ean13', $ean13)->first();

        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => __('Producto no encontrado con código: ') . $ean13
            ], 404);
        }

        return response()->json([
            'success' => true,
            'producto' => [
                'uuid' => $producto->uuid,
                'nombre' => $producto->nombre,
                'ean13' => $producto->ean13,
                'stock' => $producto->stock
            ]
        ]);
    }
}
