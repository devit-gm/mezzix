# 🚀 Análisis de Rendimiento - MEZZIX

## 🔍 Auditoría Realizada

Proyecto: Laravel 10 multi-tenant POS  
Fecha: 2026-02-03  
Controladores auditados: FichasController, MesasController

---

## 🐌 Problemas Detectados

### 1. **N+1 Queries - CRÍTICO** 🔴

#### Problema en `index()` (línea 114-131)
```php
// ❌ PROBLEMA: Calcula precio para cada ficha SIN eager loading
foreach ($fichasMostrar as $ficha) {
    // Esto ejecuta queries para productos, servicios, gastos
    $ficha->precio = $this->fichaService->calcularImporte($ficha);
}

foreach ($fichas as $ficha) {
    $ficha->precio = $this->fichaService->calcularImporte($ficha);
}
```

**Impacto:** Si tienes 50 fichas → **150+ queries** (50 fichas × 3 tablas)

**✅ SOLUCIÓN:**
```php
// Eager loading de relaciones antes del loop
$fichasMostrar = Ficha::with(['productos', 'servicios', 'gastos'])
    ->where('estado', 0)
    ->get();

foreach ($fichasMostrar as $ficha) {
    $ficha->precio = $this->fichaService->calcularImporte($ficha);
}
```

---

#### Problema en `productos()` (línea 526)
```php
$ficha = Ficha::find($uuid);
// Luego se accede a $ficha->productos SIN eager loading
```

**Impacto:** Query adicional cada vez que accedes a productos

**✅ SOLUCIÓN:**
```php
$ficha = Ficha::with(['productos', 'servicios'])->find($uuid);
```

---

#### Problema en `lista()` (línea 1247)
```php
$productosFicha = FichaProducto::with('producto:uuid,nombre,precio,imagen,combinado,iva')
    ->where('id_ficha', $uuid)
    ->get();

foreach ($productosFicha as $productoFicha) {
    $productoFicha->borrable = true; // OK, pero...
}
```

**Problema menor:** Calculas `borrable` en loop cuando podría ser un atributo

---

### 2. **Queries Repetidas en Bucles** 🟠

#### Problema en `storeproductos()` (línea 887-930)
```php
foreach ($productos as $producto) {
    // ❌ Se ejecuta query por cada producto
    $producto = Producto::with('composicion.componenteProducto')->find($productoFicha->id_producto);
    
    if ($producto->combinado == 1) {
        foreach ($producto->composicion as $composicion) {
            // Más queries aquí
        }
    }
}
```

**Impacto:** Si añades 10 productos → **20-30 queries**

**✅ SOLUCIÓN:**
```php
// Precargar TODOS los productos de una vez
$productosIds = $productos->pluck('id_producto')->unique();
$productosData = Producto::with('composicion.componenteProducto')
    ->whereIn('uuid', $productosIds)
    ->get()
    ->keyBy('uuid');

foreach ($productos as $productoFicha) {
    $producto = $productosData[$productoFicha->id_producto];
    // Sin query adicional!
}
```

---

### 3. **Sin Caché en Consultas Frecuentes** 🟡

#### Problema: Ajustes sin cache
```php
// Se repite en CADA request
$ajustes = DB::connection('site')->table('ajustes')->first();
```

**Impacto:** Query innecesaria en cada petición

**✅ SOLUCIÓN:**
```php
// En AppServiceProvider boot():
$ajustes = Cache::remember('ajustes_' . $site->uuid, 3600, function () {
    return DB::connection('site')->table('ajustes')->first();
});
app()->instance('ajustes', $ajustes);

// En controladores:
$ajustes = app('ajustes'); // ✅ Ya lo haces en algunos lugares
```

---

#### Problema: Familias sin cache (línea 445)
```php
$familias = Familia::orderBy('posicion')->get();
```

**Impacto:** Query en cada vista de productos

**✅ SOLUCIÓN:**
```php
$familias = Cache::remember('familias_site_' . $site->uuid, 3600, function () {
    return Familia::orderBy('posicion')->get();
});
```

---

### 4. **Queries Dentro de Loops** 🔴

#### Problema en `usuarios()` (línea 642-660)
```php
$usuariosFicha = FichaUsuario::where('id_ficha', $uuid)->get();

foreach ($usuariosFicha as $usuarioFicha) {
    // ❌ Query por cada usuario
    $usuarioFicha->usuario = User::find($usuarioFicha->user_id);
}
```

**Impacto:** 1 + N queries (10 usuarios = 11 queries)

**✅ SOLUCIÓN:**
```php
$usuariosFicha = FichaUsuario::with('usuario')
    ->where('id_ficha', $uuid)
    ->get();
// 1 query en lugar de N+1
```

---

### 5. **Falta de Índices en Base de Datos** ⚠️

Revisa si estas columnas tienen índices:

```sql
-- Verificar índices existentes
SHOW INDEX FROM fichas;
SHOW INDEX FROM ficha_productos;
SHOW INDEX FROM ficha_usuarios;
```

**Índices recomendados:**
```sql
-- Fichas
CREATE INDEX idx_fichas_estado ON fichas(estado);
CREATE INDEX idx_fichas_fecha_hora ON fichas(fecha, hora);
CREATE INDEX idx_fichas_user_id ON fichas(user_id);
CREATE INDEX idx_fichas_tipo ON fichas(tipo);

-- FichaProductos
CREATE INDEX idx_ficha_productos_id_ficha ON ficha_productos(id_ficha);
CREATE INDEX idx_ficha_productos_id_producto ON ficha_productos(id_producto);
CREATE INDEX idx_ficha_productos_estado ON ficha_productos(estado);

-- FichaUsuarios
CREATE INDEX idx_ficha_usuarios_id_ficha ON ficha_usuarios(id_ficha);
CREATE INDEX idx_ficha_usuarios_user_id ON ficha_usuarios(user_id);

-- Productos
CREATE INDEX idx_productos_familia_uuid ON productos(familia_uuid);
CREATE INDEX idx_productos_barcode ON productos(barcode);
```

---

### 6. **Transacciones Lentas** 🟡

#### Problema en `destroylista()` (línea 1283)
```php
return DB::transaction(function () use ($uuid, $uuid2) {
    // Múltiples deletes individuales
    $fichaProductos = FichaProducto::where(...)->get();
    foreach ($fichaProductos as $fichaProducto) {
        $fichaProducto->delete(); // Delete uno por uno
    }
});
```

**✅ SOLUCIÓN:**
```php
// Delete masivo en una query
FichaProducto::where('id_ficha', $uuid)
    ->where('id_producto', $uuid2)
    ->delete();
```

---

### 7. **Sin Paginación en Listas Grandes** 🟠

#### Problema en `index()`
```php
$fichas = Ficha::query()
    ->with(['usuario', 'inscritos'])
    ->where('estado', 0)
    ->get(); // ❌ Trae TODAS las fichas
```

**Impacto:** Si tienes 1000 fichas → overhead enorme

**✅ SOLUCIÓN:**
```php
$fichas = Ficha::query()
    ->with(['usuario', 'inscritos', 'productos', 'servicios', 'gastos'])
    ->where('estado', 0)
    ->orderBy('fecha', 'asc')
    ->paginate(50); // ✅ 50 por página

// En vista:
{{ $fichas->links() }}
```

---

## 📊 Resumen de Optimizaciones Prioritarias

| Prioridad | Problema | Ubicación | Impacto | Dificultad |
|-----------|----------|-----------|---------|------------|
| 🔴 **ALTA** | N+1 en index() | FichasController:114 | **150+ queries** | Fácil |
| 🔴 **ALTA** | N+1 en storeproductos() | FichasController:887 | **20-30 queries** | Media |
| 🔴 **ALTA** | N+1 en usuarios() | FichasController:642 | **10+ queries** | Fácil |
| 🟠 **MEDIA** | Sin cache ajustes | Múltiples | Query repetida | Fácil |
| 🟠 **MEDIA** | Sin cache familias | FichasController:445 | Query repetida | Fácil |
| 🟠 **MEDIA** | Sin paginación | index() | Memoria alta | Fácil |
| 🟡 **BAJA** | Transacciones lentas | destroylista() | 5-10 deletes → 1 | Media |
| ⚪ **INFO** | Índices faltantes | Base de datos | Mejoría general | Fácil |

---

## 🚀 Plan de Acción Recomendado

### **Fase 1: Quick Wins (1 hora)**
Optimizaciones fáciles con gran impacto:

1. **Añadir eager loading en index()**
```php
$fichas = Ficha::with(['productos', 'servicios', 'gastos', 'usuario', 'inscritos'])
    ->where('estado', 0)
    ->get();
```

2. **Cache de ajustes y familias**
```php
$ajustes = Cache::remember('ajustes_' . $site->uuid, 3600, fn() => 
    DB::connection('site')->table('ajustes')->first()
);

$familias = Cache::remember('familias_' . $site->uuid, 3600, fn() =>
    Familia::orderBy('posicion')->get()
);
```

3. **Eager loading en usuarios()**
```php
$usuariosFicha = FichaUsuario::with('usuario')->where('id_ficha', $uuid)->get();
```

---

### **Fase 2: Índices (30 minutos)**

Crear migration con índices:

```php
php artisan make:migration add_performance_indexes

// En la migration:
Schema::table('fichas', function (Blueprint $table) {
    $table->index('estado');
    $table->index(['fecha', 'hora']);
    $table->index('user_id');
    $table->index('tipo');
});

Schema::table('ficha_productos', function (Blueprint $table) {
    $table->index('id_ficha');
    $table->index('id_producto');
    $table->index('estado');
});

Schema::table('ficha_usuarios', function (Blueprint $table) {
    $table->index('id_ficha');
    $table->index('user_id');
});
```

---

### **Fase 3: Refactoring Medio (2-3 horas)**

1. Paginación en index()
2. Optimizar loops con queries batch
3. Usar bulk deletes

---

## 💡 Herramientas de Monitoreo

### Laravel Debugbar (Desarrollo)
```bash
composer require barryvdh/laravel-debugbar --dev
```

Te mostrará:
- Número de queries por request
- Queries duplicadas
- Tiempo de ejecución

### Laravel Telescope (Producción/Staging)
```bash
composer require laravel/telescope
php artisan telescope:install
```

Monitorea:
- Queries lentas
- Requests lentos
- Excepciones

### Query Log Manual
```php
// Activar en routes/web.php o controller:
DB::enableQueryLog();

// ... tu código ...

// Ver queries ejecutadas:
dd(DB::getQueryLog());
```

---

## 📈 Mejora Esperada

### Antes (estimado)
- **index() con 50 fichas:** ~200 queries, ~2-3 segundos
- **storeproductos() 10 items:** ~30 queries, ~500ms
- **usuarios() 10 usuarios:** ~12 queries, ~300ms

### Después (con optimizaciones Fase 1 + 2)
- **index() con 50 fichas:** ~5 queries, ~300ms (**85% más rápido**)
- **storeproductos() 10 items:** ~3 queries, ~100ms (**80% más rápido**)
- **usuarios() 10 usuarios:** ~2 queries, ~50ms (**83% más rápido**)

---

## 🎯 Priorización

**Si solo tienes 1 hora:**
1. Eager loading en `index()` 
2. Cache de ajustes
3. Eager loading en `usuarios()`

**Resultado:** 70-80% de mejora en velocidad percibida

---

¿Quieres que empiece aplicando las optimizaciones de **Fase 1 (Quick Wins)**? Son cambios sencillos con gran impacto 🚀

**Generado por:** Rio 😄  
**Proyecto:** MEZZIX Performance Audit
