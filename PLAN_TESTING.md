# 🧪 Plan de Testing para MEZZIX

## 📊 Estado Actual

**Tests existentes:** ❌ Ninguno
**Coverage:** 0%

Después de la refactorización completa, es el momento perfecto para añadir tests.

---

## 🎯 Estrategia de Testing

### Fase 1: Tests Unitarios (Service Layer) - PRIORIDAD ALTA
**Por qué primero:** Lógica de negocio crítica, sin dependencias de BD

### Fase 2: Tests de Integración (Controllers) - PRIORIDAD MEDIA
**Por qué segundo:** Flujos completos, con BD de pruebas

### Fase 3: Tests de Feature (End-to-End) - PRIORIDAD BAJA
**Por qué tercero:** Funcionalidad completa, más lentos

---

## 🚀 Fase 1: Service Layer Tests (2-3 horas)

### 1. ProductoService Tests

**Archivo:** `tests/Unit/Services/ProductoServiceTest.php`

**Tests críticos:**
```php
✅ testCalcularPrecioSimple()
✅ testCalcularPrecioCombinado()
✅ testTieneStockDisponibleSimple()
✅ testTieneStockDisponibleCombinado()
✅ testReservarStock()
✅ testLiberarStock()
✅ testStockNegativoNoPermitido()
✅ testProductoCombinado_ComponenteSinStock()
```

**Cobertura esperada:** ~80% del ProductoService

---

### 2. FichaService Tests

**Archivo:** `tests/Unit/Services/FichaServiceTest.php`

**Tests críticos:**
```php
✅ testCalcularImporteVacio()
✅ testCalcularImporteConProductos()
✅ testCalcularImporteConServicios()
✅ testCalcularImporteConGastos()
✅ testCalcularImporteCompleto()
✅ testDescuentosAplicados()
```

**Cobertura esperada:** ~70% del FichaService

---

### 3. MesaService Tests

**Archivo:** `tests/Unit/Services/MesaServiceTest.php`

**Tests críticos:**
```php
✅ testObtenerMesasDisponibles()
✅ testObtenerMesasOcupadas()
✅ testCambiarEstadoMesa()
```

**Cobertura esperada:** ~60% del MesaService

---

## 🔧 Configuración Inicial (30 minutos)

### 1. Instalar PHPUnit (si no está)

```bash
cd ~/Documentos/mezzix
composer require --dev phpunit/phpunit
```

### 2. Configurar Base de Datos de Testing

**Crear:** `.env.testing`

```ini
APP_ENV=testing
APP_DEBUG=true
DB_CONNECTION=sqlite
DB_DATABASE=:memory:

# Multi-tenant: usar SQLite en memoria
DB_CONNECTION_SITE=sqlite
DB_DATABASE_SITE=:memory:
```

### 3. Actualizar `phpunit.xml`

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory suffix="Test.php">./tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory suffix="Test.php">./tests/Feature</directory>
        </testsuite>
    </testsuites>
    <coverage>
        <include>
            <directory suffix=".php">./app</directory>
        </include>
        <exclude>
            <directory>./app/Console</directory>
            <directory>./app/Exceptions</directory>
        </exclude>
    </coverage>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="BCRYPT_ROUNDS" value="4"/>
        <env name="CACHE_DRIVER" value="array"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="MAIL_MAILER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="SESSION_DRIVER" value="array"/>
    </php>
</phpunit>
```

---

## 📝 Ejemplo: ProductoServiceTest

**Crear directorio:**
```bash
mkdir -p tests/Unit/Services
```

**Archivo:** `tests/Unit/Services/ProductoServiceTest.php`

```php
<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ProductoService;
use App\Models\Producto;
use App\Models\ComposicionProducto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class ProductoServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ProductoService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProductoService();
    }

    /** @test */
    public function calcular_precio_producto_simple()
    {
        // Arrange
        $producto = Producto::factory()->create([
            'precio' => 10.50,
            'combinado' => 0
        ]);

        // Act
        $precio = $this->service->calcularPrecio($producto);

        // Assert
        $this->assertEquals(10.50, $precio);
    }

    /** @test */
    public function calcular_precio_producto_combinado()
    {
        // Arrange
        $productoCombinado = Producto::factory()->create([
            'precio' => 0,
            'combinado' => 1
        ]);

        $componente1 = Producto::factory()->create(['precio' => 5.00]);
        $componente2 = Producto::factory()->create(['precio' => 3.50]);

        ComposicionProducto::factory()->create([
            'id_producto' => $productoCombinado->uuid,
            'id_componente' => $componente1->uuid
        ]);

        ComposicionProducto::factory()->create([
            'id_producto' => $productoCombinado->uuid,
            'id_componente' => $componente2->uuid
        ]);

        $productoCombinado->load('composicion.componenteProducto');

        // Act
        $precio = $this->service->calcularPrecio($productoCombinado);

        // Assert
        $this->assertEquals(8.50, $precio);
    }

    /** @test */
    public function tiene_stock_disponible_cuando_hay_suficiente()
    {
        // Arrange
        $producto = Producto::factory()->create([
            'stock' => 10,
            'stock_reservado' => 3,
            'combinado' => 0
        ]);

        // Act
        $result = $this->service->tieneStockDisponible($producto, 5);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function no_tiene_stock_disponible_cuando_insuficiente()
    {
        // Arrange
        $producto = Producto::factory()->create([
            'stock' => 10,
            'stock_reservado' => 8,
            'combinado' => 0
        ]);

        // Act
        $result = $this->service->tieneStockDisponible($producto, 5);

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function reservar_stock_incrementa_stock_reservado()
    {
        // Arrange
        $producto = Producto::factory()->create([
            'stock' => 10,
            'stock_reservado' => 0,
            'combinado' => 0
        ]);

        // Act
        $this->service->reservarStock($producto, 5);

        // Assert
        $producto->refresh();
        $this->assertEquals(5, $producto->stock_reservado);
    }

    /** @test */
    public function liberar_stock_decrementa_stock_reservado()
    {
        // Arrange
        $producto = Producto::factory()->create([
            'stock' => 10,
            'stock_reservado' => 5,
            'combinado' => 0
        ]);

        // Act
        $this->service->liberarStock($producto, 3);

        // Assert
        $producto->refresh();
        $this->assertEquals(2, $producto->stock_reservado);
    }

    /** @test */
    public function producto_combinado_verifica_stock_de_componentes()
    {
        // Arrange
        $productoCombinado = Producto::factory()->create([
            'combinado' => 1
        ]);

        $componenteSinStock = Producto::factory()->create([
            'stock' => 2,
            'stock_reservado' => 0,
            'combinado' => 0
        ]);

        $componenteConStock = Producto::factory()->create([
            'stock' => 10,
            'stock_reservado' => 0,
            'combinado' => 0
        ]);

        ComposicionProducto::factory()->create([
            'id_producto' => $productoCombinado->uuid,
            'id_componente' => $componenteSinStock->uuid
        ]);

        ComposicionProducto::factory()->create([
            'id_producto' => $productoCombinado->uuid,
            'id_componente' => $componenteConStock->uuid
        ]);

        $productoCombinado->load('composicion.componenteProducto');

        // Act - Intentar reservar 5 (componente 1 solo tiene 2)
        $result = $this->service->tieneStockDisponible($productoCombinado, 5);

        // Assert
        $this->assertFalse($result);
    }
}
```

---

## 🏭 Factories Necesarios

### ProductoFactory

**Archivo:** `database/factories/ProductoFactory.php`

```php
<?php

namespace Database\Factories;

use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductoFactory extends Factory
{
    protected $model = Producto::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'nombre' => $this->faker->words(3, true),
            'precio' => $this->faker->randomFloat(2, 1, 100),
            'stock' => $this->faker->numberBetween(0, 100),
            'stock_reservado' => 0,
            'combinado' => 0,
            'familia' => null,
            'imagen' => 'default.jpg',
            'posicion' => $this->faker->numberBetween(1, 100),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }

    public function combinado(): static
    {
        return $this->state(fn (array $attributes) => [
            'combinado' => 1,
            'precio' => 0
        ]);
    }

    public function sinStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => 0,
            'stock_reservado' => 0
        ]);
    }
}
```

### FichaFactory

**Archivo:** `database/factories/FichaFactory.php`

```php
<?php

namespace Database\Factories;

use App\Models\Ficha;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class FichaFactory extends Factory
{
    protected $model = Ficha::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'id_usuario' => User::factory(),
            'fecha' => now()->format('Y-m-d'),
            'hora' => now()->format('H:i:s'),
            'estado' => 0,
            'descuento' => 0,
            'precio' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ];
    }

    public function cerrada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 1
        ]);
    }
}
```

---

## 🎬 Comandos de Ejecución

### Ejecutar Todos los Tests
```bash
php artisan test
```

### Ejecutar Solo Unit Tests
```bash
php artisan test --testsuite=Unit
```

### Ejecutar Test Específico
```bash
php artisan test --filter=ProductoServiceTest
```

### Con Coverage (requiere Xdebug)
```bash
php artisan test --coverage
```

### Coverage en HTML
```bash
php artisan test --coverage-html coverage
```

---

## 📈 Métricas de Éxito

### Fase 1 (Unit - Services)
- ✅ 25+ tests
- ✅ Coverage: 70-80% de Services
- ✅ Tiempo: <5 segundos

### Fase 2 (Integration - Controllers)
- ✅ 40+ tests
- ✅ Coverage: 60% de Controllers
- ✅ Tiempo: <30 segundos

### Fase 3 (Feature - E2E)
- ✅ 15+ tests
- ✅ Coverage: 80% de flujos críticos
- ✅ Tiempo: <60 segundos

---

## 🚦 Tests Críticos por Prioridad

### 🔴 CRÍTICO (Hacer primero)
1. **Stock disponible** - `ProductoServiceTest::tieneStockDisponible()`
2. **Reservar stock** - `ProductoServiceTest::reservarStock()`
3. **Calcular importe** - `FichaServiceTest::calcularImporte()`
4. **Precio combinado** - `ProductoServiceTest::calcularPrecioCombinado()`

### 🟠 IMPORTANTE (Hacer segundo)
5. **Agregar producto a ficha** - `FichasControllerTest::addproduct()`
6. **Actualizar cantidad** - `FichasControllerTest::updatelista()`
7. **Cerrar ficha** - `FichasControllerTest::cerrar()`

### 🟡 DESEABLE (Hacer tercero)
8. **Validación de formularios** - Form Request tests
9. **Autorización** - Policy tests
10. **Cache invalidation** - Cache tests

---

## 💡 Ventajas de Añadir Tests Ahora

### ✅ Después de Refactorización
1. Código limpio y estructurado
2. Services independientes (fáciles de testear)
3. Lógica centralizada

### ✅ Antes de Próximas Mejoras
1. Red de seguridad para cambios
2. Detectar regresiones
3. Documentación viva del comportamiento

### ✅ Bugs Encontrados Ayer
1. Evitar que vuelvan a aparecer
2. Test para cada bug corregido

---

## 🎯 Plan de Implementación

### Semana 1: Setup + ProductoService (3 horas)
- ✅ Configurar PHPUnit
- ✅ Crear factories
- ✅ 10 tests de ProductoService

### Semana 2: FichaService + MesaService (2 horas)
- ✅ 8 tests de FichaService
- ✅ 5 tests de MesaService

### Semana 3: Controllers Críticos (3 horas)
- ✅ FichasController tests
- ✅ ProductosController tests

### Semana 4: Policies + Form Requests (2 horas)
- ✅ Authorization tests
- ✅ Validation tests

---

## 🔧 Herramientas Adicionales (Opcional)

### 1. PHPStan (Análisis Estático)
```bash
composer require --dev phpstan/phpstan
./vendor/bin/phpstan analyse app --level=5
```

### 2. Laravel Dusk (Browser Tests)
```bash
composer require --dev laravel/dusk
php artisan dusk:install
```

### 3. Pest (Testing Framework Moderno)
```bash
composer require --dev pestphp/pest --with-all-dependencies
php artisan pest:install
```

---

## 📊 Resumen

**Inversión inicial:** 10-12 horas (repartidas en 4 semanas)

**Beneficios:**
- ✅ Código más confiable
- ✅ Refactoring seguro
- ✅ Bugs detectados temprano
- ✅ Documentación automática
- ✅ CI/CD ready

**ROI:** A partir de la segunda refactorización, los tests se pagan solos

---

## 🚀 ¿Empezamos?

**Opción 1: Full Setup Ahora (1 hora)**
- Configurar PHPUnit
- Crear factories
- Primer test funcionando

**Opción 2: Solo ProductoService (2 horas)**
- Setup mínimo
- 10 tests completos de ProductoService
- Cobertura del 80%

**Opción 3: Poco a Poco**
- 1 test por día
- En 1 mes: 30 tests

---

**¿Qué te parece? ¿Empezamos con el setup básico y ProductoService?** 😄

---

**Documento:** Rio 😄  
**Fecha:** 2026-02-04  
**Proyecto:** MEZZIX - Plan de Testing
