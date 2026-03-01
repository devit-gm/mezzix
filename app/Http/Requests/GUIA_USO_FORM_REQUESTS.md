# Guía de Uso: Form Requests en MEZZIX

## 📋 Form Requests Creados

1. **StoreFichaRequest** - Crear fichas/eventos
2. **UpdateFichaRequest** - Editar fichas/eventos
3. **StoreProductoRequest** - Crear productos
4. **UpdateProductoRequest** - Editar productos
5. **AbrirMesaRequest** - Abrir mesa
6. **CerrarMesaRequest** - Cerrar mesa
7. **ActualizarMesaRequest** - Actualizar datos de mesa
8. **StoreReservaRequest** - Crear reservas (por implementar)

---

## 🔄 Refactorización: Antes vs Después

### ❌ ANTES (validación en controlador)

```php
// FichasController::store()
public function store(Request $request)
{
    // ❌ Validación inline en el controlador
    $request->validate([
        'descripcion' => 'max:255',
        'fecha' => 'required|date',
        'tipo' => 'required',
        'foto_evento' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048'
    ]);
    
    // ❌ Lógica para manejar campos vacíos
    $descripcion = '';
    if ($request->descripcion == null) {
        $descripcion = '';
    } else {
        $descripcion = $request->descripcion;
    }
    
    // ... resto del código
}
```

### ✅ DESPUÉS (usando Form Request)

```php
use App\Http\Requests\StoreFichaRequest;

// FichasController::store()
public function store(StoreFichaRequest $request)
{
    // ✅ Datos ya validados y preparados
    // ✅ Autorización ya verificada
    
    $fotoEvento = null;
    if ($request->hasFile('foto_evento')) {
        $fotoEvento = $request->file('foto_evento')->store('eventos', 'public');
    }
    
    $ficha = Ficha::create([
        'uuid' => (string) Uuid::uuid4(),
        'descripcion' => $request->descripcion, // Ya limpio
        'fecha' => $request->fecha,
        'tipo' => $request->tipo,
        'foto_evento' => $fotoEvento,
        // ...
    ]);
    
    return redirect()->route('fichas.index')
        ->with('success', 'Ficha creada correctamente');
}
```

---

## 🎯 Ejemplos de Uso por Controlador

### 1. FichasController

```php
use App\Http\Requests\StoreFichaRequest;
use App\Http\Requests\UpdateFichaRequest;

class FichasController extends Controller
{
    // Crear ficha
    public function store(StoreFichaRequest $request)
    {
        // Datos ya validados según el tipo de ficha
        // Si es tipo 4 (evento), validación incluye aforo_maximo, precio, etc.
        
        $ficha = Ficha::create([
            'uuid' => (string) Uuid::uuid4(),
            'user_id' => Auth::id(),
            'descripcion' => $request->descripcion,
            'fecha' => $request->fecha,
            'tipo' => $request->tipo,
            'hora' => $request->hora,
            'precio' => $request->precio,
            'aforo_maximo' => $request->aforo_maximo,
            // ...
        ]);
        
        return redirect()->route('fichas.index')
            ->with('success', 'Ficha creada correctamente');
    }
    
    // Actualizar ficha
    public function update(UpdateFichaRequest $request, $uuid)
    {
        $ficha = Ficha::findOrFail($uuid);
        $this->authorize('update', $ficha);
        
        $ficha->update($request->validated());
        
        return redirect()->route('fichas.show', $uuid)
            ->with('success', 'Ficha actualizada correctamente');
    }
}
```

### 2. ProductosController

```php
use App\Http\Requests\StoreProductoRequest;
use App\Http\Requests\UpdateProductoRequest;

class ProductosController extends Controller
{
    // Crear producto
    public function store(StoreProductoRequest $request)
    {
        $this->authorize('create', Producto::class);
        
        // Manejar imagen
        $imagenPath = null;
        if ($request->hasFile('imagen')) {
            $imagenPath = $request->file('imagen')->store('productos', 'public');
        }
        
        $producto = Producto::create([
            'uuid' => (string) Uuid::uuid4(),
            'nombre' => $request->nombre,
            'familia_uuid' => $request->familia_uuid,
            'precio' => $request->precio,
            'precio_compra' => $request->precio_compra,
            'iva' => $request->iva ?? 21,
            'stock' => $request->stock ?? 0,
            'stock_minimo' => $request->stock_minimo ?? 0,
            'barcode' => $request->barcode,
            'imagen' => $imagenPath,
            'combinado' => $request->combinado ?? false,
        ]);
        
        return redirect()->route('productos.index')
            ->with('success', 'Producto creado correctamente');
    }
    
    // Actualizar producto
    public function update(UpdateProductoRequest $request, $uuid)
    {
        $producto = Producto::findOrFail($uuid);
        $this->authorize('update', $producto);
        
        // Manejar nueva imagen si se subió
        if ($request->hasFile('imagen')) {
            // Eliminar imagen anterior
            if ($producto->imagen) {
                Storage::disk('public')->delete($producto->imagen);
            }
            $producto->imagen = $request->file('imagen')->store('productos', 'public');
        }
        
        $producto->update($request->except('imagen'));
        
        return redirect()->route('productos.index')
            ->with('success', 'Producto actualizado correctamente');
    }
}
```

### 3. MesasController

```php
use App\Http\Requests\AbrirMesaRequest;
use App\Http\Requests\CerrarMesaRequest;
use App\Http\Requests\ActualizarMesaRequest;

class MesasController extends Controller
{
    // Abrir mesa
    public function abrir(AbrirMesaRequest $request, $mesaId)
    {
        return DB::transaction(function () use ($request, $mesaId) {
            $mesa = Ficha::where('uuid', $mesaId)
                ->lockForUpdate()
                ->firstOrFail();
            
            $this->authorize('abrir', $mesa);
            
            $mesa->update([
                'estado_mesa' => 'ocupada',
                'camarero_id' => Auth::id(),
                'numero_comensales' => $request->numero_comensales,
                'hora_apertura' => now(),
                'observaciones' => $request->notas ?? ''
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Mesa abierta correctamente'
            ]);
        });
    }
    
    // Cerrar mesa
    public function cerrar(CerrarMesaRequest $request, $mesaId)
    {
        return DB::transaction(function () use ($request, $mesaId) {
            $mesa = Ficha::where('uuid', $mesaId)
                ->with(['productos.producto', 'servicios.servicio'])
                ->lockForUpdate()
                ->firstOrFail();
            
            $this->authorize('cerrar', $mesa);
            
            // Calcular totales...
            $importeTotal = $this->calcularImporte($mesa);
            $propina = $request->propina ?? 0;
            
            // Crear recibo...
            FichaRecibo::create([
                'uuid' => (string) Uuid::uuid4(),
                'id_ficha' => $mesa->uuid,
                'user_id' => $mesa->camarero_id,
                'tipo' => 1,
                'estado' => 1,
                'precio' => $importeTotal + $propina,
                'fecha' => now()
            ]);
            
            // Cerrar mesa...
            $mesa->update([
                'estado_mesa' => 'cerrada',
                'hora_cierre' => now(),
                'precio' => $importeTotal + $propina
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Mesa cerrada correctamente'
            ]);
        });
    }
    
    // Actualizar mesa
    public function actualizar(ActualizarMesaRequest $request, $mesaUuid)
    {
        $mesa = Ficha::findOrFail($mesaUuid);
        $this->authorize('update', $mesa);
        
        $mesa->update($request->validated());
        
        return redirect()->back()
            ->with('success', 'Mesa actualizada correctamente');
    }
}
```

---

## 📦 Estructura de un Form Request

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExampleRequest extends FormRequest
{
    /**
     * 1. AUTORIZACIÓN
     * Determina si el usuario puede hacer esta request
     * (Generalmente devuelve true y se usa Policy en el controlador)
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 2. REGLAS DE VALIDACIÓN
     * Define todas las reglas de validación
     */
    public function rules(): array
    {
        return [
            'campo' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'precio' => 'required|numeric|min:0',
        ];
    }

    /**
     * 3. MENSAJES PERSONALIZADOS
     * Define mensajes de error personalizados
     */
    public function messages(): array
    {
        return [
            'campo.required' => 'Este campo es obligatorio.',
            'email.unique' => 'Este email ya está registrado.',
        ];
    }

    /**
     * 4. PREPARAR DATOS (Opcional)
     * Modifica datos antes de validar
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'campo' => strtolower($this->campo),
        ]);
    }

    /**
     * 5. AFTER VALIDATION (Opcional)
     * Ejecuta lógica después de validar
     */
    protected function passedValidation(): void
    {
        // Hacer algo después de validar
    }
}
```

---

## 🎨 Validaciones Condicionales

### Ejemplo: StoreFichaRequest

```php
public function rules(): array
{
    $ajustes = \DB::connection('site')->table('ajustes')->first();
    $modoAgenciaEventos = $ajustes && $ajustes->modo_operacion === 'agencia_eventos';

    $rules = [
        'descripcion' => 'nullable|max:255',
        'fecha' => 'required|date',
        'tipo' => 'required|integer|in:1,2,3,4',
    ];

    // ✅ Reglas condicionales según el tipo de ficha
    if ($this->input('tipo') == 4 || $modoAgenciaEventos) {
        $rules['precio'] = 'nullable|numeric|min:0';
        $rules['aforo_maximo'] = 'required|integer|min:1|max:10000';
        $rules['foto_evento'] = 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048';
    }

    return $rules;
}
```

### Ejemplo: UpdateProductoRequest (ignorar registro actual)

```php
use Illuminate\Validation\Rule;

public function rules(): array
{
    $productoUuid = $this->route('uuid');

    return [
        'nombre' => 'required|string|max:255',
        'barcode' => [
            'nullable',
            'string',
            'max:255',
            // ✅ Ignorar el registro actual en unique
            Rule::unique('productos', 'barcode')->ignore($productoUuid, 'uuid')
        ],
    ];
}
```

---

## 🔍 Acceso a Datos Validados

```php
// En el controlador, después de recibir el FormRequest

// Opción 1: Acceso directo (recomendado)
$ficha = Ficha::create($request->validated());

// Opción 2: Acceso individual
$nombre = $request->nombre;
$precio = $request->precio;

// Opción 3: Solo algunos campos validados
$data = $request->only(['nombre', 'precio', 'stock']);

// Opción 4: Excluir campos
$data = $request->except(['_token', '_method']);

// Opción 5: Todos los datos validados
$allValidated = $request->validated();
```

---

## 🚨 Manejo de Errores

### En el Controlador
Los Form Requests automáticamente:
1. Validan los datos
2. Si falla, redirigen de vuelta con errores
3. Si pasa, continúa la ejecución

```php
// ❌ NO necesitas hacer esto:
public function store(StoreFichaRequest $request)
{
    if ($validator->fails()) { // ❌ Innecesario
        return redirect()->back()->withErrors($validator);
    }
    
    // ...
}

// ✅ Solo escribe:
public function store(StoreFichaRequest $request)
{
    // Los datos ya están validados aquí
    $ficha = Ficha::create($request->validated());
    // ...
}
```

### En las Vistas (Blade)

```blade
{{-- Mostrar errores globales --}}
@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- Mostrar error de un campo específico --}}
<input type="text" name="nombre" value="{{ old('nombre') }}" 
       class="@error('nombre') is-invalid @enderror">
@error('nombre')
    <span class="invalid-feedback">{{ $message }}</span>
@enderror
```

---

## 📊 Form Requests Disponibles

| Form Request | Uso | Campos Principales |
|--------------|-----|-------------------|
| **StoreFichaRequest** | Crear ficha/evento | descripcion, fecha, tipo, aforo_maximo (si tipo=4) |
| **UpdateFichaRequest** | Editar ficha/evento | descripcion, fecha, tipo, observaciones |
| **StoreProductoRequest** | Crear producto | nombre, familia_uuid, precio, stock, barcode |
| **UpdateProductoRequest** | Editar producto | nombre, precio, stock (barcode unique ignore) |
| **AbrirMesaRequest** | Abrir mesa | numero_comensales, notas |
| **CerrarMesaRequest** | Cerrar mesa | metodo_pago, propina |
| **ActualizarMesaRequest** | Actualizar mesa | descripcion, numero_mesa, numero_comensales |

---

## 💡 Beneficios

✅ **Código limpio** - Validación fuera del controlador  
✅ **Reutilizable** - Mismas reglas para API y Web  
✅ **Testeable** - Fácil testear validaciones de forma aislada  
✅ **Mensajes personalizados** - En español, claros y útiles  
✅ **Autorización integrada** - Método `authorize()` disponible  
✅ **Preparación de datos** - Hook `prepareForValidation()`  
✅ **DRY** - No repetir validaciones en múltiples lugares

---

## 🚀 Próximos Pasos

1. **Refactorizar Controladores**
   - Reemplazar `$request->validate()` con Form Requests
   - FichasController: métodos store() y update()
   - ProductosController: métodos store() y update()
   - MesasController: métodos abrir(), cerrar(), actualizar()

2. **Crear Form Requests Faltantes**
   - StoreReservaRequest
   - UpdateReservaRequest
   - StoreProveedorRequest
   - StoreAlbaranRequest

3. **Testing**
   - Crear tests para cada Form Request
   - Verificar validaciones funcionan correctamente

---

**Generado por:** Rio 😄  
**Fecha:** 2026-02-03  
**Proyecto:** MEZZIX - Refactorización Phase 2
