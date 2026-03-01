# ✅ Testing de Optimizaciones - MEZZIX

## 🚀 Optimizaciones Aplicadas (Fase 1)

### 1. Eager Loading en `index()`
**Archivo:** `app/Http/Controllers/FichasController.php:88`
**Cambio:**
```php
// ANTES
->with(['usuario', 'inscritos']);

// AHORA
->with(['usuario', 'inscritos', 'productos', 'servicios', 'gastos']);
```
**Impacto esperado:** Reduce ~150 queries a ~5 queries

---

### 2. Cache de Ajustes
**Archivo:** `app/Providers/AppServiceProvider.php:47`
**Cambio:**
```php
// ANTES
$ajustes = DB::connection('site')->table('ajustes')->first();

// AHORA
$ajustes = \Cache::remember("ajustes_site_{$site->uuid}", 3600, function () {
    return DB::connection('site')->table('ajustes')->first();
});
```
**Impacto esperado:** Elimina 1 query por request

---

### 3. Eager Loading en `usuarios()`
**Archivo:** `app/Http/Controllers/FichasController.php:657`
**Cambio:**
```php
// ANTES
FichaUsuario::where('id_ficha', $ficha->uuid)->get()->keyBy('user_id');

// AHORA
FichaUsuario::with('usuario')->where('id_ficha', $ficha->uuid)->get()->keyBy('user_id');
```
**Impacto esperado:** Reduce N+1 queries (10 usuarios = 11 queries → 2 queries)

---

### 4. Cache de Familias
**Archivo:** `app/Http/Controllers/FichasController.php:449`
**Cambio:**
```php
// ANTES
$familias = Familia::orderBy('posicion')->get();

// AHORA
$familias = \Cache::remember("familias_site_{$site->uuid}", 3600, function () {
    return Familia::orderBy('posicion')->get();
});
```
**Impacto esperado:** Elimina 1 query por vista de productos

---

### 5. Eager Loading en `productos()`
**Archivo:** `app/Http/Controllers/FichasController.php:535`
**Cambio:**
```php
// ANTES
$ficha = Ficha::find($uuid);
$ajustes = DB::connection('site')->table('ajustes')->first();

// AHORA
$ficha = Ficha::with(['productos', 'servicios'])->find($uuid);
$ajustes = app('ajustes');
```
**Impacto esperado:** Elimina 2 queries

---

## 🧪 Plan de Pruebas

### ⚠️ ANTES DE PROBAR EN PRODUCCIÓN

1. **Backup de la base de datos**
```bash
# Crear backup
php artisan backup:run
# o manualmente:
mysqldump -u usuario -p base_de_datos > backup_$(date +%Y%m%d_%H%M%S).sql
```

2. **Probar en entorno de desarrollo primero**
```bash
# Limpiar cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Verificar configuración
php artisan config:cache
```

---

## 📋 Checklist de Pruebas

### 🟢 PRUEBA 1: Index de Fichas (CRÍTICA)

**Ruta:** `/fichas`

**Qué probar:**
- [ ] Se muestran todas las fichas correctamente
- [ ] Los precios aparecen calculados
- [ ] Los usuarios inscritos se ven bien
- [ ] Filtros funcionan (cerradas/abiertas)
- [ ] No hay errores en log

**Cómo verificar rendimiento:**
```php
// Añadir temporalmente en FichasController::index() después de línea 88:
\DB::enableQueryLog();

// ... código ...

// Antes de return view:
\Log::info('Queries ejecutadas: ' . count(\DB::getQueryLog()));
\Log::info(\DB::getQueryLog());
```

**Resultado esperado:**
- **Antes:** 150-200 queries
- **Ahora:** 5-10 queries
- **Tiempo:** <500ms

---

### 🟢 PRUEBA 2: Ajustes Cacheados

**Ruta:** Cualquier página

**Qué probar:**
- [ ] El sitio carga correctamente
- [ ] Los ajustes se aplican (logo, colores, modo)
- [ ] No hay errores 500

**Cómo verificar cache:**
```bash
# Ver logs
tail -f storage/logs/laravel.log

# Verificar cache funcionando
php artisan tinker
>>> Cache::has('ajustes_site_XXXXXX');
>>> Cache::get('ajustes_site_XXXXXX');
```

**Limpiar cache si hay problemas:**
```bash
php artisan cache:clear
```

---

### 🟢 PRUEBA 3: Vista de Usuarios

**Ruta:** `/fichas/{uuid}/usuarios`

**Qué probar:**
- [ ] Lista de usuarios se muestra
- [ ] Usuarios marcados aparecen correctamente
- [ ] Número de invitados correcto
- [ ] Checkbox funcionan

**Verificar queries:**
```php
// En usuarios() después de línea 657:
\DB::enableQueryLog();
// ... código ...
\Log::info('Queries usuarios: ' . count(\DB::getQueryLog()));
```

**Resultado esperado:**
- **Antes:** 12+ queries (con 10 usuarios)
- **Ahora:** 2-3 queries

---

### 🟢 PRUEBA 4: Vista de Productos por Familia

**Ruta:** `/fichas/{uuid}/productos/{familia_uuid}`

**Qué probar:**
- [ ] Lista de productos se carga
- [ ] Precios correctos
- [ ] Stock disponible se muestra
- [ ] Añadir producto funciona

**Verificar cache de familias:**
```bash
# Debe cargar rápido en segunda visita
```

---

### 🟢 PRUEBA 5: Familias (Vista inicial)

**Ruta:** `/fichas/{uuid}/familias`

**Qué probar:**
- [ ] Grid de familias se muestra
- [ ] Click en familia lleva a productos
- [ ] Sin errores

---

## 🔍 Monitoreo de Queries (Opcional pero Recomendado)

### Instalar Laravel Debugbar (Solo Development)

```bash
composer require barryvdh/laravel-debugbar --dev
```

**Configuración:** Ya funciona automáticamente en desarrollo

**Uso:**
1. Abre cualquier página
2. Mira la barra inferior
3. Click en "Queries" para ver todas las queries

**Buscar problemas:**
- ⚠️ Queries duplicadas (mismo SQL repetido)
- ⚠️ N+1 (muchas queries similares con IDs diferentes)
- ⚠️ Queries lentas (>100ms)

---

## 🚨 Troubleshooting

### Problema 1: Error "Class 'Cache' not found"
```php
// Cambiar en el código:
\Cache::remember(...)
// Por:
\Illuminate\Support\Facades\Cache::remember(...)
```

### Problema 2: Cache no se limpia
```bash
# Limpiar todos los caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Recompilar
php artisan config:cache
php artisan route:cache
```

### Problema 3: Familias no se actualizan
```bash
# Limpiar cache específico
php artisan tinker
>>> Cache::forget('familias_site_XXXXXX');
```

**Solución permanente:** Invalidar cache al crear/editar familias:
```php
// En FamiliaController::store() y update():
Cache::forget("familias_site_" . app('site')->uuid);
```

### Problema 4: Ajustes no se actualizan
```bash
# Limpiar cache de ajustes
php artisan tinker
>>> Cache::forget('ajustes_site_XXXXXX');
```

**Solución permanente:** Invalidar al editar ajustes:
```php
// En AjustesController::update():
Cache::forget("ajustes_site_" . $site->uuid);
```

---

## 📊 Comparación Antes/Después

### Herramienta de Medición

Añade esto temporalmente al principio y final de `index()`:

```php
public function index(Request $request)
{
    $startTime = microtime(true);
    \DB::enableQueryLog();
    
    // ... todo el código ...
    
    $endTime = microtime(true);
    $executionTime = ($endTime - $startTime) * 1000; // en milisegundos
    $queryCount = count(\DB::getQueryLog());
    
    \Log::info("INDEX PERFORMANCE: {$executionTime}ms, {$queryCount} queries");
    
    return view(...);
}
```

**Ver resultados:**
```bash
tail -f storage/logs/laravel.log | grep "INDEX PERFORMANCE"
```

---

## ✅ Criterios de Éxito

### Fase 1 es exitosa si:

1. ✅ **Funcionalidad intacta**
   - Todas las páginas cargan
   - Sin errores 500
   - Datos correctos mostrados

2. ✅ **Mejora de rendimiento**
   - Index: <10 queries (vs 150-200 antes)
   - Tiempo: <500ms (vs 2-3 segundos antes)
   - Usuarios: <5 queries (vs 12+ antes)

3. ✅ **Sin efectos secundarios**
   - Cache se limpia cuando editas datos
   - Precios calculados correctamente
   - Stock actualizado en tiempo real

---

## 🎯 Siguiente Paso (Si Fase 1 OK)

Una vez verificado que todo funciona:

### Fase 2: Índices de Base de Datos

```bash
php artisan make:migration add_performance_indexes
```

Ver `ANALISIS_RENDIMIENTO.md` sección "Fase 2" para los índices.

---

## 📝 Notas Importantes

1. **Cache de 1 hora (3600 segundos)**
   - Ajustes: 1 hora
   - Familias: 1 hora
   - Si necesitas cambiar, modifica el segundo parámetro

2. **Invalidación manual necesaria**
   - Al editar ajustes → limpiar cache
   - Al crear/editar familias → limpiar cache
   - Ver "Troubleshooting Problema 3 y 4"

3. **Laravel Debugbar solo en desarrollo**
   - No se mostrará en producción
   - Si aparece en producción, revisar `.env`: `APP_DEBUG=false`

---

**Generado por:** Rio 😄  
**Fecha:** 2026-02-03  
**Proyecto:** MEZZIX Performance Testing
