# 🔧 Fix: Productos Siguen Mostrándose Como Agotados

## 🔴 Problema

Después de activar "Permitir comprar sin stock" en ajustes, los productos **siguen mostrándose como agotados** en la vista.

**Síntomas:**
- ✅ Ajuste activado en BD
- ✅ Código corregido
- ❌ Vista sigue mostrando productos agotados

---

## 🔍 Causa

El cache de ajustes no se invalidó después del cambio.

**Flujo del cache:**
1. Primera carga → Ajustes se guardan en cache (1 hora)
2. Cambias el ajuste en BD → Cache sigue teniendo valor antiguo
3. Vista usa cache → Muestra agotados con datos viejos

---

## ✅ Solución

### Opción 1: Limpiar Cache Manualmente (Rápido)

```bash
cd ~/Documentos/mezzix

# Limpiar cache con permisos
sudo rm -rf storage/framework/cache/data/*

# Limpiar vistas
php artisan view:clear

# Limpiar config
php artisan config:clear
```

**Después:**
- Refrescar navegador con **Ctrl+F5** (hard refresh)

---

### Opción 2: Script Automático

```bash
cd ~/Documentos/mezzix
./limpiar-cache.sh
```

**Nota:** Requiere introducir contraseña de sudo

---

### Opción 3: Esperar 1 Hora

El cache de ajustes expira automáticamente después de 1 hora (3600 segundos).

**Definido en:**
- `AppServiceProvider.php`: `Cache::remember("ajustes_site_{$uuid}", 3600, ...)`
- `get_ajustes()` helper: `Cache::remember("ajustes_site_{$uuid}", 3600, ...)`

---

## 🎯 Verificación del Código

### El Código YA Está Correcto ✅

**Archivo:** `app/Http/Controllers/FichasController.php:720`

```php
public function productos($uuid, $uuid2)
{
    // ... obtener ajustes
    
    if ($ajustes->permitir_comprar_sin_stock == 1) {
        // ✅ Si permite sin stock → colecciones vacías
        $productosAgotados = collect();
        $productosStockBajo = collect();
    } else {
        // Solo si control activo → calcular agotados
        $agotadosSimples = DB::connection('site')
            ->table('productos')
            ->whereRaw('(stock - COALESCE(stock_reservado, 0)) <= 0')
            ->pluck('uuid');
        
        // ... resto de lógica
        $productosAgotados = $productos->whereIn('uuid', $idsAgotados);
    }
    
    return view('fichas.productos', compact('productosAgotados', ...));
}
```

**Lógica:**
- ✅ Si `permitir_comprar_sin_stock == 1` → NO calcula agotados
- ✅ Si `permitir_comprar_sin_stock == 0` → SÍ calcula agotados

---

### Vista También Correcta ✅

**Archivo:** `resources/views/fichas/productos.blade.php:37`

```blade
@if($productosAgotados->contains('uuid', $producto->uuid))
    <div class="producto-card producto-agotado">
        <!-- ... -->
        {{ __('Agotado') }}
    </div>
@else
    <div class="producto-card">
        <!-- Producto normal -->
    </div>
@endif
```

**Lógica:**
- ✅ Si producto está en `$productosAgotados` → Muestra "Agotado"
- ✅ Si colección vacía → Ningún producto agotado

---

## 🧪 Testing Completo

### 1. Verificar Ajuste en BD

```bash
php artisan tinker --execute="
\$ajustes = DB::connection('site')->table('ajustes')->first();
echo 'permitir_comprar_sin_stock: ' . \$ajustes->permitir_comprar_sin_stock;
"
# Debería mostrar: 1
```

### 2. Verificar Cache

```bash
php artisan tinker --execute="
\$site = get_site();
\$cached = Cache::get('ajustes_site_' . \$site->uuid);
echo 'Cache: ' . (\$cached ? \$cached->permitir_comprar_sin_stock : 'no existe');
"
# Debería mostrar: 1
```

### 3. Invalidar Cache Manualmente

```bash
php artisan tinker --execute="
\$site = get_site();
Cache::forget('ajustes_site_' . \$site->uuid);
echo 'Cache invalidado';
"
```

**O con sudo:**
```bash
sudo rm -rf storage/framework/cache/data/*
```

### 4. Probar en Navegador

1. Ctrl+F5 (hard refresh)
2. Ir a Fichas → Añadir Producto
3. Ver lista de productos
4. ✅ **NO deberían aparecer como "Agotado"**

---

## 🔄 Invalidación Automática de Cache

Para que los cambios en ajustes se reflejen inmediatamente, el `AjustesController` **ya tiene** invalidación de cache:

**Archivo:** `app/Http/Controllers/AjustesController.php`

```php
public function update(Request $request)
{
    // ... actualizar ajustes
    
    // 🚀 Invalidar cache
    $site = get_site();
    if ($site) {
        Cache::forget("ajustes_site_{$site->uuid}");
    }
    
    return redirect()->back()->with('success', 'Ajustes actualizados');
}
```

**Si esto NO está funcionando:**
- Problema de permisos en `storage/framework/cache/`
- Driver de cache diferente (Redis, Memcached)

---

## 💡 Solución Permanente: Permisos de Cache

```bash
cd ~/Documentos/mezzix

# Ajustar permisos correctamente
sudo chown -R www-data:www-data storage/framework/cache
sudo chmod -R 775 storage/framework/cache

# O si usas tu usuario
sudo chown -R $USER:www-data storage/framework/cache
sudo chmod -R 775 storage/framework/cache
```

**Esto permite:**
- ✅ Aplicación web puede escribir cache
- ✅ Comandos artisan pueden limpiar cache
- ✅ `Cache::forget()` funciona correctamente

---

## 📊 Resumen

### Problema:
- ❌ Productos se muestran agotados aunque ajuste esté activado

### Causa:
- ❌ Cache de ajustes no se invalidó

### Solución:
1. ✅ Código ya está correcto (respeta ajuste)
2. ✅ Limpiar cache manualmente: `sudo rm -rf storage/framework/cache/data/*`
3. ✅ Hard refresh navegador: Ctrl+F5
4. ✅ Ajustar permisos para que cache funcione correctamente

### Verificado:
- ✅ Ajuste en BD = 1
- ✅ Código del controlador respeta ajuste
- ✅ Vista usa variable `$productosAgotados`
- ✅ Si colección vacía → No muestra agotados

---

**Fecha:** 2026-02-03  
**Por:** Rio 😄  
**Proyecto:** MEZZIX - Cache de Ajustes Stock
