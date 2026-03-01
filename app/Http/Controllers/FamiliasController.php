<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Familia;
use App\Models\Producto;
use Illuminate\Support\Facades\File;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Auth;
use App\Services\ImageService;

class FamiliasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $familias = Familia::orderBy('posicion')->get();
        
        // Obtener familias que tienen productos en una sola consulta
        $familiasConProductos = Producto::whereIn('familia', $familias->pluck('uuid'))
            ->distinct()
            ->pluck('familia')
            ->toArray();
        
        foreach ($familias as $familia) {
            //si la familia tiene productos no se puede borrar
            //si el usuario activo es administrador
            if (Auth::check() && Auth::user()->role_id == 1) {
                $familia->borrable = !in_array($familia->uuid, $familiasConProductos);
            } else {
                $familia->borrable = false;
            }
        }
        return view('familias.index', compact('familias'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|max:255',
            'imagen' => 'required|image|mimes:png,jpg,jpeg,webp|max:2048',
            'posicion' => 'required'
        ]);
        
        // Procesar y redimensionar imagen
        $imageName = ImageService::processAndSave($request->imagen, 'public/images');
        
        // Copiar a public/images para compatibilidad
        $sourcePath = storage_path('app/public/images/' . $imageName);
        $destPath = public_path('images/' . $imageName);
        copy($sourcePath, $destPath);

        // Determinar si mostrar_en_cocina debe establecerse
        $mostrarEnCocina = 0;
        $ajustes = app('App\\Models\\Ajustes')::first();
        if ($ajustes && $ajustes->modo_operacion === 'mesas') {
            $mostrarEnCocina = $request->has('mostrar_en_cocina') ? 1 : 0;
        }

        Familia::create([
            'uuid' => (string) Uuid::uuid4(),
            'nombre' => $request->nombre,
            'imagen' => $imageName,
            'posicion' => $request->posicion,
            'mostrar_en_cocina' => $mostrarEnCocina
        ]);
        
        // 🚀 OPTIMIZACIÓN: Invalidar caché de familias
        $site = get_site();
        \Cache::forget("familias_site_{$site->uuid}");
        \Cache::forget('familias_grid_html');
        
        return redirect()->route('familias.index')
            ->with('success', __('Familia creada con éxito.'));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $familia = Familia::find($id);
        return view('familias.show', compact('familia'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'nombre' => 'required|max:255',
            'imagen' => 'image|mimes:png,jpg,jpeg,webp|max:2048',
            'posicion' => 'required'
        ]);
        $familia = Familia::find($id);

        if ($request->imagen != null) {
            if (File::exists(public_path('images') . '/'  . $familia->imagen)) {
                File::delete(public_path('images') . '/'  . $familia->imagen);
            }
            if (File::exists(storage_path('app/public/images') . '/'  . $familia->imagen)) {
                File::delete(storage_path('app/public/images') . '/'  . $familia->imagen);
            }
            
            // Procesar y redimensionar imagen
            $imageName = ImageService::processAndSave($request->imagen, 'public/images');
            
            // Copiar a public/images para compatibilidad
            $sourcePath = storage_path('app/public/images/' . $imageName);
            $destPath = public_path('images/' . $imageName);
            copy($sourcePath, $destPath);
        } else {
            $imageName = $familia->imagen;
        }

        // Determinar si mostrar_en_cocina debe establecerse
        $mostrarEnCocina = 0;
        $ajustes = app('App\\Models\\Ajustes')::first();
        if ($ajustes && $ajustes->modo_operacion === 'mesas') {
            $mostrarEnCocina = $request->has('mostrar_en_cocina') ? 1 : 0;
        }

        $familia->update([
            'nombre' => $request->nombre,
            'imagen' => $imageName,
            'posicion' => $request->posicion,
            'mostrar_en_cocina' => $mostrarEnCocina
        ]);
        
        // 🚀 OPTIMIZACIÓN: Invalidar caché de familias
        $site = get_site();
        \Cache::forget("familias_site_{$site->uuid}");
        \Cache::forget('familias_grid_html');
        
        return redirect()->route('familias.index')
            ->with('success', __('Familia actualizada con éxito.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $familia = Familia::find($id);
        if (File::exists(public_path('images') . '/'  . $familia->imagen)) {
            File::delete(public_path('images') . '/'  . $familia->imagen);
        }
        $familia->delete();
        
        // 🚀 OPTIMIZACIÓN: Invalidar caché de familias
        $site = get_site();
        \Cache::forget("familias_site_{$site->uuid}");
        \Cache::forget('familias_grid_html');
        
        return redirect()->route('familias.index')
            ->with('success', __('Familia eliminada con éxito'));
    }

    /**
     * Show the form for creating a new post.
     */
    public function create()
    {
        return view('familias.create');
    }

    /**
     * Show the form for editing the specified post.
     *
     * @param  int  $id
     */
    public function edit($uuid)
    {
        $familia = Familia::where('uuid', $uuid)->firstOrFail();
        $familia->borrable = !Producto::where('familia', $uuid)->exists();
        return view('familias.edit', compact('familia'));
    }

    public function view($id)
    {
        $familia = Familia::find($id);
        $productos = Producto::where('familia', $id)->orderBy('nombre')->get();
        return view('familias.view', compact('productos', 'familia'));
    }
}
