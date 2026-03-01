# 🔧 Diagnóstico: Errores 419 y 500 Tras Login

## 🔍 Errores Identificados

### Error 419: Page Expired (CSRF Token)
**Síntoma:** Al intentar login, aparece "Page Expired" o error 419

**Causas posibles:**
1. Token CSRF expirado (formulario abierto >3 días)
2. Sesión expirada pero formulario todavía abierto
3. Dominio de sesión incorrecto
4. Cookies bloqueadas/no se guardan

### Error 500: Internal Server Error
**Síntoma:** Página en blanco o error genérico tras login

**Causas posibles:**
1. Error en `AppServiceProvider` al cargar ajustes/sitio
2. Conexión a BD tenant falla
3. Cache corrupto
4. Eager loading falla en modelo

---

## 🔍 Configuración Actual Detectada

### Sesiones (.env)
```ini
SESSION_DRIVER=file
SESSION_LIFETIME=4320  # 3 días (en minutos)
SESSION_DOMAIN=192.168.1.137  # ⚠️ IP específica
SESSION_SECURE_COOKIE=false
SESSION_SAME_SITE=lax
```

### ⚠️ Problemas Detectados

#### 1. SESSION_DOMAIN con IP
```ini
SESSION_DOMAIN=192.168.1.137
```

**Problema:** Si accedes por localhost, otro dominio, o la IP cambia, las sesiones no funcionan.

**Solución:**
```ini
# Opción A: Sin dominio (recomendado multi-tenant)
SESSION_DOMAIN=null

# Opción B: Dominio específico
SESSION_DOMAIN=.mezzix.local

# Opción C: Vacío (deja que Laravel detecte)
SESSION_DOMAIN=
```

#### 2. SESSION_LIFETIME muy largo (3 días)
**Problema:** Tokens CSRF expiran antes que la sesión

**Explicación:**
- Sesión: 4320 min (3 días)
- Token CSRF por defecto: expira con sesión
- Pero si dejas el formulario abierto, token puede expirar

---

## 🛠️ Soluciones

### Solución 1: Ajustar SESSION_DOMAIN (CRÍTICO)

**Editar `.env`:**

```bash
# ANTES
SESSION_DOMAIN=192.168.1.137

# DESPUÉS (para multi-tenant)
SESSION_DOMAIN=null
```

**Aplicar:**
```bash
cd ~/Documentos/mezzix
php artisan config:clear
php artisan cache:clear
```

---

### Solución 2: Mejorar Manejo de CSRF en Login

**Crear middleware para regenerar token:**

Archivo: `app/Http/Middleware/RefreshCsrfToken.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RefreshCsrfToken
{
    public function handle(Request $request, Closure $next)
    {
        // Si es GET a la página de login, regenerar token
        if ($request->isMethod('GET') && $request->is('login')) {
            $request->session()->regenerateToken();
        }
        
        return $next($request);
    }
}
```

**Registrar en `app/Http/Kernel.php`:**

```php
protected $middlewareGroups = [
    'web' => [
        // ... otros middlewares ...
        \App\Http\Middleware\RefreshCsrfToken::class,
    ],
];
```

---

### Solución 3: Capturar Error 419 y Redirigir

**Archivo: `app/Exceptions/Handler.php`**

Buscar el método `render()` y añadir:

```php
public function render($request, Throwable $exception)
{
    // Capturar error 419 (CSRF Token Mismatch)
    if ($exception instanceof \Illuminate\Session\TokenMismatchException) {
        return redirect()
            ->route('login')
            ->with('error', 'Tu sesión ha expirado. Por favor, inicia sesión de nuevo.');
    }
    
    return parent::render($request, $exception);
}
```

---

### Solución 4: Verificar AppServiceProvider no Falla

**Problema:** Si `AppServiceProvider::boot()` falla cuando no hay sitio, causa 500

**Revisar:** `app/Providers/AppServiceProvider.php`

```php
public function boot(Request $request): void
{
    $domain = $request->getHost();
    
    $site = Site::where('dominio', $domain)->first();
    
    if (!$site) {
        // ⚠️ Esto causa 500 si no hay sitio
        abort(404, 'Site not found.');
    }
    
    // ... resto del código
}
```

**Mejorar:**

```php
public function boot(Request $request): void
{
    // Saltar si es comando CLI o ruta de login
    if ($this->app->runningInConsole() || $request->is('login', 'register', 'password/*')) {
        return;
    }
    
    $domain = $request->getHost();
    $site = Site::where('dominio', $domain)->first();
    
    if (!$site) {
        // Solo en rutas web
        if (!$request->is('api/*')) {
            abort(404, 'Site not found.');
        }
        return;
    }
    
    // ... resto del código
}
```

---

### Solución 5: Limpiar Sesiones y Cache Corruptos

```bash
cd ~/Documentos/mezzix

# Limpiar sesiones antiguas
rm -rf storage/framework/sessions/*

# Limpiar cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

# Recompilar
php artisan config:cache
php artisan route:cache
```

---

### Solución 6: Aumentar Logging de Errores

**Archivo: `config/logging.php`**

Cambiar nivel de log temporalmente:

```php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['single'],
        'ignore_exceptions' => false,
    ],
    
    'single' => [
        'driver' => 'single',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('LOG_LEVEL', 'debug'), // ← Cambiar a 'debug'
    ],
],
```

---

## 🧪 Testing

### Test 1: Verificar SESSION_DOMAIN

```bash
# Acceder por diferentes rutas y probar login:
# http://192.168.1.137/login
# http://localhost/login
# http://eldespiste.local/login

# Si SESSION_DOMAIN=null, todas deberían funcionar
```

### Test 2: Reproducir Error 419

```bash
1. Abrir /login
2. Esperar 30 minutos (o cambiar SESSION_LIFETIME a 1)
3. Intentar login
4. Debería redirigir con mensaje en lugar de 419
```

### Test 3: Verificar Logs

```bash
# En otra terminal, monitorear logs en tiempo real:
tail -f ~/Documentos/mezzix/storage/logs/laravel.log

# Hacer login y ver si aparecen errores
```

---

## 📊 Configuración Recomendada

**Archivo: `.env`**

```ini
# Sesiones
SESSION_DRIVER=database  # ← Mejor para multi-tenant (o redis)
SESSION_LIFETIME=1440    # ← 24 horas (más seguro)
SESSION_DOMAIN=null      # ← Permite cualquier dominio
SESSION_SECURE_COOKIE=false  # true solo si usas HTTPS
SESSION_SAME_SITE=lax

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=info  # debug solo para desarrollo
```

**Si cambias a `SESSION_DRIVER=database`:**

```bash
# Crear tabla de sesiones
php artisan session:table
php artisan migrate

# Limpiar sesiones viejas periódicamente (cron)
php artisan schedule:work
```

---

## 🎯 Plan de Acción Inmediato

### Paso 1: Cambiar SESSION_DOMAIN (2 minutos)

```bash
cd ~/Documentos/mezzix

# Editar .env
nano .env
# Cambiar: SESSION_DOMAIN=null

# Aplicar
php artisan config:clear
php artisan cache:clear
```

### Paso 2: Limpiar Sesiones Corruptas (1 minuto)

```bash
rm -rf storage/framework/sessions/*
```

### Paso 3: Probar Login (1 minuto)

Acceder y probar login desde:
- http://192.168.1.137/login
- Otro navegador/incógnito

### Paso 4: Monitorear Logs (Si sigue fallando)

```bash
tail -f storage/logs/laravel.log
# ... hacer login y ver errores
```

---

## 🚨 Si Error 500 Persiste

### Verificar Permis os de Storage

```bash
cd ~/Documentos/mezzix
chmod -R 775 storage bootstrap/cache
chown -R $USER:www-data storage bootstrap/cache
```

### Verificar Conexión BD Tenant

```php
// Probar en tinker
php artisan tinker

>>> $site = App\Models\Site::where('db_name', 'eldespiste')->first();
>>> config(['database.connections.tenant' => [
    'driver' => 'mysql',
    'host' => $site->db_host,
    'database' => $site->db_name,
    'username' => $site->db_user,
    'password' => $site->db_password,
]]);
>>> DB::connection('tenant')->getPdo();
// Si falla aquí, problema de conexión BD
```

---

## 📝 Archivos a Modificar

1. ✅ `.env` → Cambiar `SESSION_DOMAIN=null`
2. ⚠️ `app/Exceptions/Handler.php` → Capturar 419
3. ⚠️ `app/Providers/AppServiceProvider.php` → No fallar en login
4. 🔄 `config/logging.php` → Aumentar log level temporalmente

---

**Generado por:** Rio 😄  
**Fecha:** 2026-02-03  
**Proyecto:** MEZZIX Login Error Diagnosis
