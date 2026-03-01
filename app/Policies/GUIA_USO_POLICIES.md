# Guía de Uso: Policies en MEZZIX

## 📋 Policies Creadas

1. **FichaPolicy** - Autorización para fichas/eventos
2. **MesaPolicy** - Autorización para sistema de mesas
3. **ProductoPolicy** - Autorización para productos

---

## 🔄 Refactorización: Antes vs Después

### ❌ ANTES (código repetido)

```php
// En FichasController::show()
public function show($uuid)
{
    $ficha = Ficha::with(['usuario', 'inscritos'])->findOrFail($uuid);
    
    // ❌ Lógica de autorización repetida
    $esAdmin = Auth::user() && Auth::user()->role_id == 1;
    $esPropietario = Auth::id() == $ficha->user_id;
    $estaEnFicha = $ficha->inscritos->contains('user_id', Auth::id());
    
    if (!$esPropietario && !$esAdmin && !$estaEnFicha && $ficha->tipo != 4) {
        abort(403, 'No tienes permiso para ver esta ficha');
    }
    
    return view('fichas.show', compact('ficha'));
}
```

### ✅ DESPUÉS (usando Policy)

```php
// En FichasController::show()
public function show($uuid)
{
    $ficha = Ficha::with(['usuario', 'inscritos'])->findOrFail($uuid);
    
    // ✅ Autorización limpia en una línea
    $this->authorize('view', $ficha);
    
    return view('fichas.show', compact('ficha'));
}
```

---

## 🎯 Ejemplos de Uso por Controlador

### 1. FichasController

```php
use App\Models\Ficha;

class FichasController extends Controller
{
    // Ver ficha
    public function show($uuid)
    {
        $ficha = Ficha::findOrFail($uuid);
        $this->authorize('view', $ficha);
        
        return view('fichas.show', compact('ficha'));
    }
    
    // Editar ficha
    public function edit($uuid)
    {
        $ficha = Ficha::findOrFail($uuid);
        $this->authorize('update', $ficha);
        
        return view('fichas.edit', compact('ficha'));
    }
    
    // Borrar ficha
    public function destroy($uuid)
    {
        $ficha = Ficha::findOrFail($uuid);
        $this->authorize('delete', $ficha);
        
        $ficha->delete();
        return redirect()->route('fichas.index')
            ->with('success', 'Ficha eliminada');
    }
    
    // Añadir productos
    public function addproduct(Request $request, $uuid, $uuid2)
    {
        $ficha = Ficha::findOrFail($uuid);
        $this->authorize('addProducts', $ficha);
        
        // ... lógica de añadir producto
    }
    
    // Gestionar gastos
    public function updategastos(Request $request, $uuid)
    {
        $ficha = Ficha::findOrFail($uuid);
        $this->authorize('manageGastos', $ficha);
        
        // ... lógica de gastos
    }
}
```

### 2. MesasController

```php
use App\Models\Ficha;

class MesasController extends Controller
{
    // Abrir mesa
    public function abrir(Request $request, $mesaId)
    {
        $mesa = Ficha::findOrFail($mesaId);
        $this->authorize('abrir', $mesa);
        
        // ... lógica de abrir mesa
    }
    
    // Cerrar mesa
    public function cerrar(Request $request, $mesaId)
    {
        $mesa = Ficha::findOrFail($mesaId);
        $this->authorize('cerrar', $mesa);
        
        // ... lógica de cerrar mesa
    }
    
    // Crear mesa (sin instancia)
    public function crearIndividual(Request $request)
    {
        $this->authorize('create', Ficha::class);
        
        // ... lógica de crear mesa
    }
    
    // Eliminar mesa
    public function eliminar($mesaUuid)
    {
        $mesa = Ficha::findOrFail($mesaUuid);
        $this->authorize('delete', $mesa);
        
        $mesa->delete();
        return redirect()->back()->with('success', 'Mesa eliminada');
    }
}
```

### 3. ProductosController

```php
use App\Models\Producto;

class ProductosController extends Controller
{
    // Crear producto
    public function create()
    {
        $this->authorize('create', Producto::class);
        
        return view('productos.create');
    }
    
    // Editar producto
    public function edit($uuid)
    {
        $producto = Producto::findOrFail($uuid);
        $this->authorize('update', $producto);
        
        return view('productos.edit', compact('producto'));
    }
    
    // Gestionar inventario
    public function inventory()
    {
        $this->authorize('manageInventory', Producto::class);
        
        $productos = Producto::all();
        return view('productos.inventory', compact('productos'));
    }
}
```

### 4. EventosPublicosController

```php
use App\Models\Ficha;

class EventosPublicosController extends Controller
{
    // Inscribirse a evento
    public function inscribirse($uuid)
    {
        $evento = Ficha::findOrFail($uuid);
        $this->authorize('inscribirse', $evento);
        
        // ... lógica de inscripción
    }
    
    // Cancelar inscripción
    public function cancelarInscripcion($uuid)
    {
        $evento = Ficha::findOrFail($uuid);
        $this->authorize('cancelarInscripcion', $evento);
        
        // ... lógica de cancelación
    }
}
```

---

## 🔒 Uso en Blade (Vistas)

### Mostrar/ocultar botones según permisos

```blade
{{-- Botón de editar ficha --}}
@can('update', $ficha)
    <a href="{{ route('fichas.edit', $ficha->uuid) }}" class="btn btn-primary">
        Editar
    </a>
@endcan

{{-- Botón de borrar ficha --}}
@can('delete', $ficha)
    <form action="{{ route('fichas.destroy', $ficha->uuid) }}" method="POST">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger">Eliminar</button>
    </form>
@endcan

{{-- Botón de inscribirse a evento --}}
@can('inscribirse', $evento)
    <form action="{{ route('eventos-publicos.inscribirse', $evento->uuid) }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-success">Inscribirse</button>
    </form>
@endcan

{{-- Botón de cerrar mesa (solo camarero asignado o admin) --}}
@can('cerrar', $mesa)
    <button class="btn btn-warning" onclick="cerrarMesa('{{ $mesa->uuid }}')">
        Cerrar Mesa
    </button>
@endcan

{{-- Sección completa solo para admin --}}
@can('viewAny', App\Models\Producto::class)
    <div class="admin-panel">
        <h3>Panel de Administración</h3>
        <!-- contenido solo para admin -->
    </div>
@endcan
```

---

## 📊 Métodos Disponibles por Policy

### FichaPolicy

| Método | Descripción | Quién puede |
|--------|-------------|-------------|
| `viewAny` | Ver lista de fichas | Todos |
| `view` | Ver ficha específica | Admin, propietario, inscrito o evento público |
| `create` | Crear ficha | Todos |
| `update` | Editar ficha | Admin o propietario |
| `delete` | Borrar ficha | Admin, propietario o inscrito (solo si estado=0) |
| `addProducts` | Añadir productos | Admin, propietario o inscrito |
| `manageUsers` | Gestionar invitados | Admin, propietario o inscrito |
| `manageGastos` | Gestionar gastos | Admin o propietario |
| `inscribirse` | Inscribirse a evento | Usuarios no inscritos (si hay aforo) |
| `cancelarInscripcion` | Cancelar inscripción | Usuarios inscritos |

### MesaPolicy

| Método | Descripción | Quién puede |
|--------|-------------|-------------|
| `viewAny` | Ver todas las mesas | Todos los camareros |
| `view` | Ver mesa específica | Todos |
| `create` | Crear mesa | Usuarios con role_id < 4 (no camareros) |
| `update` | Editar mesa | Usuarios con role_id < 4 |
| `delete` | Eliminar mesa | Usuarios con role_id < 4 (solo si libre) |
| `abrir` | Abrir mesa | Cualquier camarero (si libre) |
| `tomar` | Tomar mesa | Cualquier camarero (si ocupada por otro) |
| `cerrar` | Cerrar mesa | Camarero asignado o admin (si ocupada) |
| `liberar` | Liberar mesa | Camarero asignado o admin (si cerrada) |
| `addProducts` | Añadir productos | Camarero asignado o admin (si ocupada) |
| `reordenar` | Reordenar mesas | Usuarios con role_id < 4 |

### ProductoPolicy

| Método | Descripción | Quién puede |
|--------|-------------|-------------|
| `viewAny` | Ver lista de productos | Todos |
| `view` | Ver producto | Todos |
| `create` | Crear producto | Usuarios con role_id < 4 |
| `update` | Editar producto | Usuarios con role_id < 4 |
| `delete` | Eliminar producto | Usuarios con role_id < 4 |
| `manageInventory` | Gestionar stock | Usuarios con role_id < 4 |

---

## 🚀 Próximos Pasos

1. **Refactorizar FichasController**
   - Reemplazar todas las verificaciones manuales con `$this->authorize()`
   - Ejemplo: líneas 101-107, 128-133, 249, 431

2. **Refactorizar MesasController**
   - Añadir `$this->authorize()` en métodos de abrir/cerrar/tomar/liberar
   - Reemplazar verificaciones de `role_id >= 4`

3. **Actualizar Vistas Blade**
   - Reemplazar `@if(Auth::user()->role_id == 1)` con `@can()`
   - Ocultar botones según permisos

4. **Testing**
   - Crear tests para cada policy
   - Verificar que los permisos funcionan correctamente

---

## 💡 Beneficios

✅ **Código más limpio** - Una línea en lugar de 5-6  
✅ **Centralizado** - Toda la lógica de permisos en un solo lugar  
✅ **Mantenible** - Cambiar permisos sin tocar controladores  
✅ **Testeable** - Fácil de probar policies de forma aislada  
✅ **Reutilizable** - Usar `@can()` en vistas automáticamente  
✅ **Legible** - `$this->authorize('update', $ficha)` es autoexplicativo

---

**Generado por:** Rio 😄  
**Fecha:** 2026-02-03  
**Proyecto:** MEZZIX - Refactorización Phase 1
