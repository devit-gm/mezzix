# 📊 Aplicación de Service Layer - MEZZIX

## ✅ Servicios Aplicados a Controladores

### FichasController

#### 1. Inyección de Dependencias
```php
protected $fichaService;
protected $productoService;

public function __construct(FichaService $fichaService, ProductoService $productoService)
{
    $this->fichaService = $fichaService;
    $this->productoService = $productoService;
    // ... middleware
}
```

#### 2. Métodos Refactorizados

| Método Original | Líneas Antes | Service Usado | Reducción |
|-----------------|--------------|---------------|-----------|
| `ObtenerImporteFicha()` | 40 líneas | `fichaService->calcularImporte()` | **-40 líneas** |
| Verificación de stock (storeproductos) | 20 líneas | `productoService->tieneStockDisponible()` | **-15 líneas** |
| Reservar stock (storeproductos) | 15 líneas | `productoService->reservarStock()` | **-12 líneas** |
| Liberar stock (destroylista) | 30 líneas | `productoService->liberarStock()` | **-25 líneas** |
| Verificar stock (updatelista) | 18 líneas | `productoService->tieneStockDisponible()` | **-15 líneas** |

**Total FichasController:** ~**107 líneas reducidas**

#### 3. Llamadas Reemplazadas

Todas las llamadas a `$this->ObtenerImporteFicha($ficha)` fueron reemplazadas por:
```php
$this->fichaService->calcularImporte($ficha)
```

**Ocurrencias reemplazadas:** 18 métodos

#### 4. Ejemplo Antes/Después

**❌ ANTES:**
```php
public function storeproductos(Request $request)
{
    // ...
    if ($ficha->estado == 0) {
        if ($producto->combinado == 1) {
            foreach ($producto->composicion as $composicion) {
                $componente = $composicion->componenteProducto;
                if ($componente && !$componente->tieneStockDisponible($cantidad)) {
                    return redirect()->back()
                        ->with('error', "Stock insuficiente de {$componente->nombre}");
                }
            }
        } else {
            if (!$producto->tieneStockDisponible($cantidad)) {
                return redirect()->back()
                    ->with('error', "Stock insuficiente de {$producto->nombre}");
            }
        }
    }
    
    if ($producto->combinado == 1) {
        $precio = 0;
        foreach ($producto->composicion as $composicion) {
            $componente = $composicion->componenteProducto;
            $precio += $componente->precio;
            if ($ficha->estado == 0) {
                $componente->reservarStock($cantidad);
            }
        }
        $producto->precio = $precio;
    } else {
        if ($ficha->estado == 0) {
            $producto->reservarStock($cantidad);
        }
    }
    // ...
}
```

**✅ DESPUÉS:**
```php
public function storeproductos(Request $request)
{
    // ...
    // Verificar stock usando ProductoService
    if ($ficha->estado == 0) {
        if (!$this->productoService->tieneStockDisponible($producto, $cantidad)) {
            $mensaje = "Stock insuficiente de {$producto->nombre}";
            return redirect()->back()->with('error', $mensaje);
        }
    }
    
    // Calcular precio si es combinado
    if ($producto->combinado == 1) {
        $precio = 0;
        foreach ($producto->composicion as $composicion) {
            $precio += $composicion->componenteProducto->precio;
        }
        $producto->precio = $precio;
    }
    
    // Reservar stock usando ProductoService
    if ($ficha->estado == 0) {
        $this->productoService->reservarStock($producto, $cantidad);
    }
    // ...
}
```

---

### MesasController

#### 1. Inyección de Dependencias
```php
protected $mesaService;

public function __construct(MesaService $mesaService)
{
    $this->mesaService = $mesaService;
}
```

#### 2. Métodos Refactorizados

| Método | Líneas Antes | Service Usado | Reducción |
|--------|--------------|---------------|-----------|
| `index()` - calcular importe | 7 líneas | `mesaService->calcularImporte()` | **-5 líneas** |
| `abrir()` - abrir mesa | 22 líneas | `mesaService->abrir()` | **-15 líneas** |
| `tomar()` - transferir mesa | 18 líneas | `mesaService->transferir()` | **-12 líneas** |

**Total MesasController:** ~**32 líneas reducidas**

#### 3. Ejemplo Antes/Después

**❌ ANTES:**
```php
public function index()
{
    // ...
    $mesas->each(function($mesa) {
        $totalProductos = $mesa->productos->sum(function($fp) {
            return $fp->producto ? $fp->producto->precio : 0;
        });
        $totalServicios = $mesa->servicios->sum(function($fs) {
            return $fs->servicio ? $fs->servicio->precio : 0;
        });
        $mesa->importe = $totalProductos + $totalServicios;
    });
    // ...
}
```

**✅ DESPUÉS:**
```php
public function index()
{
    // ...
    $mesas->each(function($mesa) {
        $mesa->importe = $this->mesaService->calcularImporte($mesa);
        // ...
    });
    // ...
}
```

**❌ ANTES (abrir):**
```php
public function abrir(AbrirMesaRequest $request, $mesaId)
{
    $this->authorize('abrir', $mesa);
    
    $mesa->update([
        'estado_mesa' => 'ocupada',
        'camarero_id' => Auth::id(),
        'numero_comensales' => $request->numero_comensales,
        'hora_apertura' => now(),
        'observaciones' => $request->notas ?? ''
    ]);
    
    \App\Models\MesaHistorial::create([
        'mesa_id' => $mesa->uuid,
        'accion' => 'abrir',
        'camarero_id' => Auth::id(),
        'detalles' => json_encode([
            'comensales' => $request->numero_comensales,
            'notas' => $request->notas
        ])
    ]);
    // ...
}
```

**✅ DESPUÉS (abrir):**
```php
public function abrir(AbrirMesaRequest $request, $mesaId)
{
    $this->authorize('abrir', $mesa);
    
    // Una sola llamada al servicio
    $this->mesaService->abrir(
        $mesa,
        Auth::id(),
        $request->numero_comensales,
        $request->notas
    );
    // ...
}
```

---

## 📊 Impacto Total

### Líneas de Código Reducidas

| Controlador | Métodos Refactorizados | Líneas Reducidas |
|-------------|------------------------|------------------|
| **FichasController** | 5 métodos principales | ~107 líneas |
| **MesasController** | 3 métodos | ~32 líneas |
| **TOTAL** | **8 métodos** | **~139 líneas** |

### Llamadas Reemplazadas

- **18 llamadas** a `ObtenerImporteFicha()` → `fichaService->calcularImporte()`
- **5 secciones** de verificación de stock → `productoService->tieneStockDisponible()`
- **3 secciones** de reserva/liberación → `productoService->reservarStock/liberarStock()`

---

## 🎯 Beneficios Obtenidos

### 1. **Código Más Limpio**
- Controllers enfocados en HTTP (requests/responses)
- Lógica de negocio en servicios reutilizables
- Métodos más cortos y legibles

### 2. **Reutilización**
```php
// Ahora puedes usar los servicios en:
// - Controladores
// - Jobs
// - Commands
// - Tests
// - API endpoints

$precio = app(FichaService::class)->calcularImporte($ficha);
```

### 3. **Testing Más Fácil**
```php
// Testear lógica de negocio de forma aislada
$fichaService = app(FichaService::class);
$precio = $fichaService->calcularImporte($ficha);
$this->assertEquals(150.50, $precio);
```

### 4. **Mantenibilidad**
- Cambios de lógica en un solo lugar
- No repetir código de cálculos
- Fácil encontrar dónde se calcula el precio

---

## 📦 Métodos de Servicio Disponibles

### FichaService (15 métodos)
✅ `calcularImporte()` - **APLICADO** (18 usos)  
✅ `calcularConsumos()`  
✅ `calcularInvitados()`  
✅ `obtenerDesglose()`  
⚪ `puedeVerFicha()` - Disponible para aplicar  
⚪ `verificarDisponibilidadInscripcion()` - Disponible  
⚪ `inscribirUsuario()` - Disponible  
⚪ `cancelarInscripcion()` - Disponible  

### MesaService (14 métodos)
✅ `calcularImporte()` - **APLICADO**  
✅ `abrir()` - **APLICADO**  
✅ `transferir()` - **APLICADO**  
⚪ `cerrar()` - Complejo, pendiente  
⚪ `liberar()` - Disponible  
⚪ `enviarACocina()` - Disponible  
⚪ `hayProductosPendientesEnCocina()` - Disponible  

### ProductoService (11 métodos)
✅ `tieneStockDisponible()` - **APLICADO** (5 usos)  
✅ `reservarStock()` - **APLICADO** (3 usos)  
✅ `liberarStock()` - **APLICADO** (3 usos)  
⚪ `confirmarStock()` - Disponible  
⚪ `calcularMargen()` - Disponible  
⚪ `estaStockBajo()` - Disponible  
⚪ `obtenerProductosStockBajo()` - Disponible  
⚪ `obtenerMasVendidos()` - Disponible  

---

## 🚀 Próximos Pasos (Opcional)

### Fase 6: Aplicar Servicios Restantes
1. Usar `inscribirUsuario()` en controlador de eventos
2. Implementar `cerrar()` completo en MesasController
3. Usar `obtenerDesglose()` en vistas de fichas
4. Implementar `obtenerProductosStockBajo()` en dashboard

### Fase 7: Testing
1. Crear tests unitarios para servicios
2. Crear tests de integración para controladores
3. Verificar cobertura de código

---

## 📄 Archivos Modificados

1. ✅ `app/Http/Controllers/FichasController.php`
   - Inyección de FichaService y ProductoService
   - 18 llamadas refactorizadas con servicios
   - ~107 líneas reducidas

2. ✅ `app/Http/Controllers/Mesas/MesasController.php`
   - Inyección de MesaService
   - 3 métodos refactorizados
   - ~32 líneas reducidas

3. ✅ `app/Services/FichaService.php` (creado previamente)
4. ✅ `app/Services/MesaService.php` (creado previamente)
5. ✅ `app/Services/ProductoService.php` (creado previamente)
6. ✅ `app/Providers/ServiceLayerServiceProvider.php` (registrado)
7. ✅ `config/app.php` (provider registrado)

---

## ✅ Status Final

### Resumen de Toda la Refactorización

| Fase | Resultado | Impacto |
|------|-----------|---------|
| **1. MesasController** | Extraído | -1137 líneas |
| **2. Policies** | 3 creadas | Autorización centralizada |
| **3. Form Requests** | 7 creados | Validación centralizada |
| **4. Refactorización** | 9 métodos | -114 líneas |
| **5. Service Layer** | 3 servicios, 40 métodos | Lógica centralizada |
| **6. Aplicar Servicios** | 8 métodos refactorizados | **-139 líneas** |

### Impacto Total Acumulado
- **-1390 líneas** de código eliminadas/simplificadas
- **40 líneas → 1 línea** para cálculos de precio
- **Arquitectura Clean:** Controllers → Services → Models
- **5 guías** de documentación

---

**Generado por:** Rio 😄  
**Fecha:** 2026-02-03  
**Proyecto:** MEZZIX - Service Layer Aplicado (Fase 6)
