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
    public function puede_verificar_stock_disponible_en_producto_simple()
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
        $this->assertTrue($result, 'Debería tener stock disponible (10 - 3 = 7, necesita 5)');
    }

    /** @test */
    public function detecta_stock_insuficiente_en_producto_simple()
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
        $this->assertFalse($result, 'No debería tener stock (10 - 8 = 2, necesita 5)');
    }

    /** @test */
    public function puede_reservar_stock_en_producto_simple()
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
    public function puede_liberar_stock_en_producto_simple()
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
    public function liberar_stock_no_permite_valores_negativos()
    {
        // Arrange
        $producto = Producto::factory()->create([
            'stock' => 10,
            'stock_reservado' => 2,
            'combinado' => 0
        ]);

        // Act
        $this->service->liberarStock($producto, 5);

        // Assert
        $producto->refresh();
        $this->assertEquals(0, $producto->stock_reservado, 'Stock reservado no puede ser negativo');
    }

    /** @test */
    public function producto_combinado_verifica_stock_de_todos_los_componentes()
    {
        // Arrange
        $productoCombinado = Producto::factory()->combinado()->create();

        $componenteConStock = Producto::factory()->create([
            'stock' => 10,
            'stock_reservado' => 0,
            'combinado' => 0
        ]);

        $componenteSinStock = Producto::factory()->create([
            'stock' => 2,
            'stock_reservado' => 0,
            'combinado' => 0
        ]);

        ComposicionProducto::factory()->create([
            'id_producto' => $productoCombinado->uuid,
            'id_componente' => $componenteConStock->uuid
        ]);

        ComposicionProducto::factory()->create([
            'id_producto' => $productoCombinado->uuid,
            'id_componente' => $componenteSinStock->uuid
        ]);

        $productoCombinado->load('composicion.componenteProducto');

        // Act - Intentar reservar 5 (componente 2 solo tiene 2)
        $result = $this->service->tieneStockDisponible($productoCombinado, 5);

        // Assert
        $this->assertFalse($result, 'Producto combinado no tiene suficiente de uno de sus componentes');
    }

    /** @test */
    public function producto_combinado_con_suficiente_stock_en_todos_componentes()
    {
        // Arrange
        $productoCombinado = Producto::factory()->combinado()->create();

        $componente1 = Producto::factory()->create([
            'stock' => 20,
            'stock_reservado' => 0,
            'combinado' => 0
        ]);

        $componente2 = Producto::factory()->create([
            'stock' => 15,
            'stock_reservado' => 0,
            'combinado' => 0
        ]);

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
        $result = $this->service->tieneStockDisponible($productoCombinado, 5);

        // Assert
        $this->assertTrue($result, 'Producto combinado tiene suficiente stock en todos sus componentes');
    }

    /** @test */
    public function puede_reservar_stock_en_producto_combinado()
    {
        // Arrange
        $productoCombinado = Producto::factory()->combinado()->create();

        $componente1 = Producto::factory()->create([
            'stock' => 20,
            'stock_reservado' => 0,
            'combinado' => 0
        ]);

        $componente2 = Producto::factory()->create([
            'stock' => 15,
            'stock_reservado' => 0,
            'combinado' => 0
        ]);

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
        $this->service->reservarStock($productoCombinado, 3);

        // Assert
        $componente1->refresh();
        $componente2->refresh();
        
        $this->assertEquals(3, $componente1->stock_reservado, 'Componente 1 debe tener 3 unidades reservadas');
        $this->assertEquals(3, $componente2->stock_reservado, 'Componente 2 debe tener 3 unidades reservadas');
    }

    /** @test */
    public function puede_liberar_stock_en_producto_combinado()
    {
        // Arrange
        $productoCombinado = Producto::factory()->combinado()->create();

        $componente1 = Producto::factory()->create([
            'stock' => 20,
            'stock_reservado' => 5,
            'combinado' => 0
        ]);

        $componente2 = Producto::factory()->create([
            'stock' => 15,
            'stock_reservado' => 5,
            'combinado' => 0
        ]);

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
        $this->service->liberarStock($productoCombinado, 3);

        // Assert
        $componente1->refresh();
        $componente2->refresh();
        
        $this->assertEquals(2, $componente1->stock_reservado, 'Componente 1 debe tener 2 unidades reservadas (5-3)');
        $this->assertEquals(2, $componente2->stock_reservado, 'Componente 2 debe tener 2 unidades reservadas (5-3)');
    }

    /** @test */
    public function producto_sin_composicion_no_lanza_error()
    {
        // Arrange
        $productoCombinado = Producto::factory()->combinado()->create();
        // Sin composición cargada

        // Act & Assert - No debería lanzar excepción
        $result = $this->service->tieneStockDisponible($productoCombinado, 1);
        
        $this->assertTrue($result, 'Producto combinado sin composición debería permitir (caso edge)');
    }
}
