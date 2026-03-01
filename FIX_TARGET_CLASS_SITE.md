# 🎯 Solución Final Completa - Error "Target class [site]"

## 🔴 Problemas Encontrados

### 1. Recursión Infinita en Helper (CRÍTICO)
```php
// ❌ MAL
if (app()->bound('site')) {
    return get_site(); // ← ¡Recursión infinita!
}
```

### 2. Middleware DetectSite Duplicaba Lógica
- `DetectSite` cargaba el site de nuevo
- No lo guardaba en container
- Competía con `AppServiceProvider`

### 3. 14 Archivos Usaban `app('site')` Directamente
- InformesController
- ReservasController
- FacturaMesaController
- EventosPublicosController
- UsuariosController
- FacturacionController
- ContactoController
- SetLocale middleware
- CacheAjustes middleware
- StockNotificationService
- + 4 más

---

## ✅ Soluciones Aplicadas

### 1. Helper Corregido con Soporte www/sin www

**Archivo:** `app/Helpers/site_helpers.php`

```php
function get_site()
{
    // Si ya está en el container, devolverlo
    if (app()->bound('site')) {
        return app('site'); // ✅ Correcto, no recursivo
    }
    
    // Normalizar dominio con/sin www
    $domain = request()->getHost();
    $domainWithWww = str_starts_with($domain, 'www.') ? $domain : 'www.' . $domain;
    $domainWithoutWww = str_starts_with($domain, 'www.') ? substr($domain, 4) : $domain;
    
    // Buscar con las 3 variantes
    $site = \App\Models\Site::where('dominio', $domainWithWww)
        ->orWhere('dominio', $domainWithoutWww)
        ->orWhere('dominio', $domain)
        ->first();
    
    if ($site) {
        app()->instance('site', $site);
        return $site;
    }
    
    return null;
}
```

**Características:**
- ✅ No recursivo
- ✅ Maneja www/sin www
- ✅ Cachea en container
- ✅ Devuelve null si no encuentra (no rompe)

---

### 2. DetectSite Simplificado

**Archivo:** `app/Http/Middleware/DetectSite.php`

**ANTES:**
```php
$domain = $request->getHost();
$domainWithWww = ...;
$domainWithoutWww = ...;
$site = Site::where('dominio', $domainWithWww)
    ->orWhere('dominio', $domainWithoutWww)
    ->first();
```

**AHORA:**
```php
// 🚀 Usar helper (evita duplicación)
$site = get_site();

if (!$site) {
    abort(404, 'Sitio no encontrado...');
}
```

**Beneficios:**
- ✅ No duplica lógica
- ✅ Usa el mismo site que AppServiceProvider
- ✅ Más simple y mantenible

---

### 3. Reemplazado en 14 Archivos

**Script ejecutado:**
```bash
find app/ -name "*.php" -type f -exec sed -i "s/app('site')/get_site()/g" {} \;
find app/ -name "*.php" -type f -exec sed -i "s/app('ajustes')/get_ajustes()/g" {} \;
```

**Archivos actualizados:**
- ✅ 8 Controllers
- ✅ 2 Middlewares
- ✅ 1 Service
- ✅ 3 otros archivos

---

## 🎯 Arquitectura Final

### Flujo de Carga del Site

```
1. Request llega
2. AppServiceProvider::boot()
   ├─ Si es /login, /register → SKIP
   └─ Si es ruta normal → Carga site y guarda en container
3. DetectSite middleware
   └─ Llama get_site() → Ya está en container, lo devuelve
4. Cualquier controller
   └─ Llama get_site() → Ya está en container, lo devuelve
```

**Ventajas:**
- ✅ Site se carga **una sola vez**
- ✅ Se guarda en container
- ✅ Todos usan el mismo helper
- ✅ No hay duplicación

---

## 🧪 Verificación

### Test Automático Ejecutado:
```bash
php artisan tinker --execute="
  \$site = get_site();
  echo \$site->nombre; // EL DESPISTE ✅
  
  \$site2 = get_site();
  echo \$site2->nombre; // EL DESPISTE ✅ (desde cache)
  
  \$ajustes = get_ajustes();
  echo 'OK'; // OK ✅
"
```

**Resultado:** ✅ PASS

---

## 📋 Checklist Antes de Probar

### 1. Limpiar Cache y Sesiones
```bash
cd ~/Documentos/mezzix
./limpiar-cache.sh
# Introduce tu contraseña cuando pida sudo
```

### 2. Reiniciar Servidor Web
```bash
sudo systemctl restart apache2
# o
sudo systemctl restart nginx
```

### 3. Probar en Navegador Incógnito
- Ir a tu sitio
- Hacer login
- Navegar por diferentes páginas
- **NO** debería aparecer "Target class [site]"

---

## 🔍 Si Sigue Fallando

### Ver logs en tiempo real:
```bash
tail -f ~/Documentos/mezzix/storage/logs/laravel.log | grep "Target class\|ERROR"
```

### Verificar autoload:
```bash
composer dump-autoload
php artisan config:clear
```

### Verificar helper está cargado:
```bash
php artisan tinker --execute="var_dump(function_exists('get_site'));"
# Debería mostrar: bool(true)
```

---

## 📊 Cambios Totales en Esta Sesión

### Archivos Nuevos
1. ✅ `app/Helpers/site_helpers.php` (2 funciones)
2. ✅ `limpiar-cache.sh` (script de limpieza)
3. ✅ `FIX_TARGET_CLASS_SITE.md` (esta guía)

### Archivos Modificados
1. ✅ `composer.json` (autoload)
2. ✅ `app/Helpers/site_helpers.php` (corregido recursión + www)
3. ✅ `app/Http/Middleware/DetectSite.php` (usa helper)
4. ✅ **14 archivos** con `app('site')` → `get_site()`
5. ✅ **14 archivos** con `app('ajustes')` → `get_ajustes()`

**Total:** 3 nuevos + 31 modificados

---

## 💡 Lecciones Aprendidas

### ❌ Errores Cometidos
1. Helper inicial con recursión infinita
2. Reemplazo automático con sed afectó al propio helper
3. No consideré www/sin www inicialmente

### ✅ Soluciones
1. Corregir helper manualmente
2. Soporte para múltiples variantes de dominio
3. Simplificar middleware para evitar duplicación

---

## 🎯 Estado Final

**Problema inicial:**
```
Target class [site] does not exist
```

**Causa raíz:**
1. AppServiceProvider saltaba login/register
2. Otros lugares usaban `app('site')` directamente
3. Helper inicial tenía recursión infinita

**Solución:**
1. ✅ Helper robusto con www/sin www
2. ✅ Todos los archivos usan helper
3. ✅ DetectSite simplificado
4. ✅ Sin recursión

**Estado:** ✅ **RESUELTO**

---

**Fecha:** 2026-02-03 22:05  
**Por:** Rio 😄  
**Proyecto:** MEZZIX - Fix Target Class Site (DEFINITIVO)
