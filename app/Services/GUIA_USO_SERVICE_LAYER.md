# Guía de Uso: Service Layer en MEZZIX

## 📦 Servicios Creados

1. **FichaService** - Lógica de negocio de fichas y eventos
2. **MesaService** - Lógica de negocio de mesas
3. **ProductoService** - Gestión de stock y productos

---

## 🎯 ¿Qué es el Service Layer?

El **Service Layer** (Capa de Servicios) extrae la lógica de negocio de los controladores a clases dedicadas.

### ✅ Beneficios

- **Reutilización**: Misma lógica usable en controladores, comandos, jobs, API
- **Testing**: Fácil testear lógica de negocio de forma aislada
- **Claridad**: Controladores más limpios y enfocados en HTTP
- **Mantenibilidad**: Cambios de lógica en un solo lugar
- **DRY**: No repetir cálculos y verificaciones

---

## 🔄 Refactorización: Antes vs Después

### ❌ ANTES (lógica en controlador)

```php
// FichasController - 40 líneas para calcular precio
private function ObtenerImporteFicha($ficha, $sumarInvitados = false)
{
    $ajustes = app()->has('ajustes') ? app('ajustes') : Ajustes::first();
    
    if (!$ajustes) {
        Log::warning('Ajustes no encontrado', ['ficha_uuid' => $ficha->uuid]);
        $ajustes = new Ajustes();
    }
    
    $precio = FichaProducto::where('id_ficha', $ficha->uuid)->sum('precio');
    $precio += FichaServicio::where('id_ficha', $ficha->uuid)->sum('precio');
    $precio += FichaGasto::where('id_ficha', $ficha->uuid)->sum('precio');
    
    if ($sumarInvitados && $ajustes->uuid) {
        $usuarios = FichaUsuario::where('id_ficha', $ficha->uuid)->get(['invitados']);
        foreach ($usuarios as $usuario) {
            $num_invitados = $usuario->invitados;
            if ($num_invitados > ($ajustes->max_invitados_cobrar ?? 0)) {
                $num_invitados = $ajustes->max_invitados_cobrar ?? 0;
            }
            if (($ajustes->primer_invitado_gratis ?? false) && $num_invitados > 0) {
                $num_invitados--;
            }
            $precio += $num_invitados * ($ajustes->precio_invitado ?? 0);
        }
    }
    
    // ... más lógica
    
    return $precio;
}
```

### ✅ DESPUÉS (usando Service)

```php
use App\Services\FichaService;

class FichasController extends Controller
{
    protected $fichaService;
    
    public function __construct(FichaService $fichaService)
    {
        $this->fichaService = $fichaService;
    }
    
    public function show(string $uuid)
    {
        $ficha = Ficha::findOrFail($uuid);
        $this->authorize('view', $ficha);
        
        // ✅ Una línea en lugar de 40
        $ficha->precio = $this->fichaService->calcularImporte($ficha, true);
        
        return view('fichas.edit', compact('ficha'));
    }
}
```

---

## 📚 FichaService

### Métodos Disponibles

```php
// Calcular importe total
$precio = $fichaService->calcularImporte($ficha, $sumarInvitados = false);

// Calcular solo consumos (sin invitados)
$consumos = $fichaService->calcularConsumos($ficha);

// Calcular coste de invitados
$invitados = $fichaService->calcularInvitados($ficha, $ajustes);

// Obtener desglose completo
$desglose = $fichaService->obtenerDesglose($ficha);
// ['productos' => 150, 'servicios' => 50, 'gastos' => 20, 'invitados' => 30, 'total' => 250]

// Verificar si usuario puede ver la ficha
$puede = $fichaService->puedeVerFicha($ficha, $userId);

// Verificar disponibilidad para inscripción
$disponibilidad = $fichaService->verificarDisponibilidadInscripcion($ficha);
// ['disponible' => true/false, 'razon' => 'Aforo completo']

// Inscribir usuario en evento
$resultado = $fichaService->inscribirUsuario($ficha, $userId);

// Cancelar inscripción
$resultado = $fichaService->cancelarInscripcion($ficha, $userId);
```

### Ejemplo de Uso en Controlador

```php
use App\Services\FichaService;

class FichasController extends Controller
{
    public function __construct(
        protected FichaService $fichaService
    ) {}
    
    public function show(string $uuid)
    {
        $ficha = Ficha::findOrFail($uuid);
        $this->authorize('view', $ficha);
        
        // Calcular precio
        $ficha->precio = $this->fichaService->calcularImporte($ficha);
        
        // Obtener desglose
        $desglose = $this->fichaService->obtenerDesglose($ficha);
        
        return view('fichas.edit', compact('ficha', 'desglose'));
    }
    
    public function inscribirse($uuid)
    {
        $ficha = Ficha::findOrFail($uuid);
        
        // Verificar disponibilidad
        $disponibilidad = $this->fichaService->verificarDisponibilidadInscripcion($ficha);
        
        if (!$disponibilidad['disponible']) {
            return redirect()->back()->with('error', $disponibilidad['razon']);
        }
        
        // Inscribir
        $resultado = $this->fichaService->inscribirUsuario($ficha, auth()->id());
        
        if (!$resultado) {
            return redirect()->back()->with('error', 'Ya estás inscrito en este evento');
        }
        
        return redirect()->back()->with('success', 'Inscrito correctamente');
    }
}
```

---

## 🍽️ MesaService

### Métodos Disponibles

```php
// Calcular importe de mesa
$importe = $mesaService->calcularImporte($mesa);

// Obtener desglose con productos agrupados
$desglose = $mesaService->obtenerDesglose($mesa);
// ['productos' => [...], 'servicios' => [...], 'total_productos' => 150, 'total_servicios' => 20, 'total' => 170]

// Verificaciones de estado
$libre = $mesaService->estaLibre($mesa);
$ocupada = $mesaService->estaOcupada($mesa);
$esCamarero = $mesaService->esCamareroAsignado($mesa, $userId);

// Abrir mesa
$mesaService->abrir($mesa, $camareroId, $numeroComensales, $notas);

// Transferir mesa a otro camarero
$mesaService->transferir($mesa, $nuevoCamareroId);

// Cerrar mesa y generar recibo
$recibo = $mesaService->cerrar($mesa, $metodoPago, $propina);

// Liberar mesa sin cerrarla
$mesaService->liberar($mesa, $userId);

// Verificar productos pendientes en cocina
$hayPendientes = $mesaService->hayProductosPendientesEnCocina($mesa);

// Enviar productos a cocina
$enviados = $mesaService->enviarACocina($mesa);

// Calcular tiempo de ocupación (minutos)
$minutos = $mesaService->calcularTiempoOcupacion($mesa);
```

### Ejemplo de Uso en Controlador

```php
use App\Services\MesaService;

class MesasController extends Controller
{
    public function __construct(
        protected MesaService $mesaService
    ) {}
    
    public function abrir(AbrirMesaRequest $request, $mesaId)
    {
        $mesa = Ficha::findOrFail($mesaId);
        $this->authorize('abrir', $mesa);
        
        // Toda la lógica está en el servicio
        $this->mesaService->abrir(
            $mesa,
            auth()->id(),
            $request->numero_comensales,
            $request->notas
        );
        
        return response()->json([
            'success' => true,
            'message' => 'Mesa abierta correctamente'
        ]);
    }
    
    public function cerrar(CerrarMesaRequest $request, $mesaId)
    {
        $mesa = Ficha::findOrFail($mesaId);
        $this->authorize('cerrar', $mesa);
        
        // Verificar productos pendientes
        if ($this->mesaService->hayProductosPendientesEnCocina($mesa)) {
            return response()->json([
                'success' => false,
                'message' => 'Hay productos pendientes en cocina'
            ], 400);
        }
        
        // Cerrar y obtener recibo
        $recibo = $this->mesaService->cerrar(
            $mesa,
            $request->metodo_pago,
            $request->propina ?? 0
        );
        
        return response()->json([
            'success' => true,
            'recibo_id' => $recibo->uuid
        ]);
    }
}
```

---

## 📦 ProductoService

### Métodos Disponibles

```php
// Verificar stock disponible (incluye combinados)
$disponible = $productoService->tieneStockDisponible($producto, $cantidad);

// Reservar stock (para fichas abiertas)
$reservado = $productoService->reservarStock($producto, $cantidad);

// Liberar stock reservado
$productoService->liberarStock($producto, $cantidad);

// Confirmar venta (descontar stock real)
$productoService->confirmarStock($producto, $cantidad);

// Calcular precio con IVA
$precioConIva = $productoService->calcularPrecioConIva($producto);

// Calcular margen de beneficio
$margen = $productoService->calcularMargen($producto);
// ['margen_porcentaje' => 45.5, 'margen_euros' => 2.50]

// Verificar si el stock está bajo
$bajo = $productoService->estaStockBajo($producto);

// Obtener productos con stock bajo
$productos = $productoService->obtenerProductosStockBajo();

// Obtener productos más vendidos
$masVendidos = $productoService->obtenerMasVendidos($limite = 10, $fechaDesde = null);
```

### Ejemplo de Uso en Controlador

```php
use App\Services\ProductoService;

class FichasController extends Controller
{
    public function __construct(
        protected ProductoService $productoService
    ) {}
    
    public function updatelista(string $fichaUuid, string $productoId, int $cantidad)
    {
        $ficha = Ficha::findOrFail($fichaUuid);
        $producto = Producto::findOrFail($productoId);
        
        // Verificar stock si estamos añadiendo
        if ($cantidad > 0 && $ficha->estado == 0) {
            if (!$this->productoService->tieneStockDisponible($producto, $cantidad)) {
                return redirect()->back()->with('error', 'Stock insuficiente');
            }
        }
        
        // Reservar stock
        if ($cantidad > 0) {
            $this->productoService->reservarStock($producto, $cantidad);
        } elseif ($cantidad < 0) {
            $this->productoService->liberarStock($producto, abs($cantidad));
        }
        
        // ... resto de lógica
        
        return redirect()->back()->with('success', 'Producto actualizado');
    }
}
```

---

## 🔧 Inyección de Dependencias

### Opción 1: Constructor Injection (Recomendado)

```php
class FichasController extends Controller
{
    public function __construct(
        protected FichaService $fichaService,
        protected ProductoService $productoService
    ) {}
    
    public function metodo()
    {
        $precio = $this->fichaService->calcularImporte($ficha);
    }
}
```

### Opción 2: Method Injection

```php
class FichasController extends Controller
{
    public function metodo(FichaService $fichaService)
    {
        $precio = $fichaService->calcularImporte($ficha);
    }
}
```

### Opción 3: Service Locator (No recomendado)

```php
$fichaService = app(FichaService::class);
$precio = $fichaService->calcularImporte($ficha);
```

---

## 🧪 Testing de Servicios

```php
use App\Services\FichaService;
use App\Models\Ficha;
use Tests\TestCase;

class FichaServiceTest extends TestCase
{
    protected FichaService $fichaService;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->fichaService = app(FichaService::class);
    }
    
    /** @test */
    public function puede_calcular_importe_de_ficha()
    {
        $ficha = Ficha::factory()->create();
        
        // Añadir productos
        FichaProducto::factory()->create([
            'id_ficha' => $ficha->uuid,
            'precio' => 10.50
        ]);
        
        $importe = $this->fichaService->calcularImporte($ficha);
        
        $this->assertEquals(10.50, $importe);
    }
    
    /** @test */
    public function verifica_disponibilidad_para_inscripcion()
    {
        $evento = Ficha::factory()->create([
            'tipo' => 4,
            'aforo_maximo' => 10,
            'inscritos_actuales' => 10
        ]);
        
        $disponibilidad = $this->fichaService->verificarDisponibilidadInscripcion($evento);
        
        $this->assertFalse($disponibilidad['disponible']);
        $this->assertEquals('Aforo completo', $disponibilidad['razon']);
    }
}
```

---

## 📊 Migración Gradual

### Paso 1: Crear servicio
```bash
# Ya creados:
app/Services/FichaService.php
app/Services/MesaService.php
app/Services/ProductoService.php
```

### Paso 2: Registrar en ServiceProvider
```php
// app/Providers/ServiceLayerServiceProvider.php
$this->app->singleton(FichaService::class, function ($app) {
    return new FichaService();
});
```

### Paso 3: Añadir al config/app.php
```php
'providers' => [
    // ...
    App\Providers\ServiceLayerServiceProvider::class,
]
```

### Paso 4: Inyectar en controladores
```php
public function __construct(FichaService $fichaService) {
    $this->fichaService = $fichaService;
}
```

### Paso 5: Reemplazar lógica
```php
// ❌ Antes
$precio = $this->ObtenerImporteFicha($ficha);

// ✅ Después
$precio = $this->fichaService->calcularImporte($ficha);
```

---

## 🎯 Métodos a Refactorizar

### FichasController

| Método Actual | Reemplazar Con | Servicio |
|---------------|----------------|----------|
| `ObtenerImporteFicha()` | `calcularImporte()` | FichaService |
| Lógica de inscripción | `inscribirUsuario()` | FichaService |
| Verificar permisos inline | `puedeVerFicha()` | FichaService |

### MesasController

| Método Actual | Reemplazar Con | Servicio |
|---------------|----------------|----------|
| Cálculos de cierre | `cerrar()` | MesaService |
| Verificar estado | `estaLibre()/estaOcupada()` | MesaService |
| Envío a cocina | `enviarACocina()` | MesaService |

### ProductosController

| Método Actual | Reemplazar Con | Servicio |
|---------------|----------------|----------|
| Verificar stock inline | `tieneStockDisponible()` | ProductoService |
| Reservar/liberar stock | `reservarStock()/liberarStock()` | ProductoService |
| Calcular margen | `calcularMargen()` | ProductoService |

---

## 💡 Mejores Prácticas

✅ **DO:**
- Servicios para lógica de negocio compleja
- Inyección de dependencias en constructor
- Métodos con nombres descriptivos
- Documentación PHPDoc
- Testing de servicios de forma aislada

❌ **DON'T:**
- No acceder a Request en servicios
- No devolver Responses desde servicios
- No mezclar lógica de presentación
- No hacer servicios "God Class" (demasiado grandes)

---

## 📄 Archivos Creados

1. ✅ `app/Services/FichaService.php` - 15 métodos
2. ✅ `app/Services/MesaService.php` - 14 métodos
3. ✅ `app/Services/ProductoService.php` - 11 métodos
4. ✅ `app/Providers/ServiceLayerServiceProvider.php` - Registro de servicios
5. ✅ `config/app.php` - Provider registrado

---

**Generado por:** Rio 😄  
**Fecha:** 2026-02-03  
**Proyecto:** MEZZIX - Service Layer Phase 5
