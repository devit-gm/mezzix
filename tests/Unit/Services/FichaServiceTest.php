<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\FichaService;
use App\Models\Ficha;
use App\Models\FichaProducto;
use App\Models\FichaServicio;
use App\Models\FichaGasto;
use App\Models\FichaUsuario;
use App\Models\Producto;
use App\Models\Servicio;
use App\Models\User;
use App\Models\Ajustes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class FichaServiceTest extends TestCase
{
    use RefreshDatabase;

    protected FichaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FichaService();
        
        // Crear ajustes por defecto (sin crear registro, mockearlo)
        // El servicio lo obtiene con get_ajustes() que puede retornar null
    }

    /** @test */
    public function puede_calcular_importe_de_ficha_vacia()
    {
        // Arrange
        $ficha = Ficha::factory()->create();

        // Act
        $importe = $this->service->calcularImporte($ficha);

        // Assert
        $this->assertEquals(0, $importe, 'Ficha vacía debe tener importe 0');
    }

    /** @test */
    public function puede_calcular_importe_con_productos()
    {
        // Arrange
        $ficha = Ficha::factory()->create();
        $producto1 = Producto::factory()->create(['precio' => 10.50]);
        $producto2 = Producto::factory()->create(['precio' => 5.25]);

        // Crear FichaProductos
        FichaProducto::create([
            'uuid' => Str::uuid(),
            'id_ficha' => $ficha->uuid,
            'id_producto' => $producto1->uuid,
            'cantidad' => 1,
            'precio' => 10.50 // Precio unitario
        ]);

        FichaProducto::create([
            'uuid' => Str::uuid(),
            'id_ficha' => $ficha->uuid,
            'id_producto' => $producto2->uuid,
            'cantidad' => 1,
            'precio' => 5.25
        ]);

        // Act
        $importe = $this->service->calcularImporte($ficha);

        // Assert
        // 10.50 + 5.25 = 15.75
        $this->assertEquals(15.75, $importe);
    }

    /** @test */
    public function puede_calcular_importe_con_servicios()
    {
        // Arrange
        $ficha = Ficha::factory()->create();
        $servicio = Servicio::factory()->create(['precio' => 20.00]);

        FichaServicio::create([
            'uuid' => Str::uuid(),
            'id_ficha' => $ficha->uuid,
            'id_servicio' => $servicio->uuid,
            'cantidad' => 1,
            'precio' => 20.00
        ]);

        // Act
        $importe = $this->service->calcularImporte($ficha);

        // Assert
        $this->assertEquals(20.00, $importe);
    }

    /** @test */
    public function puede_calcular_importe_con_gastos()
    {
        // Arrange
        $ficha = Ficha::factory()->create();
        $user = User::factory()->create();

        FichaGasto::create([
            'uuid' => Str::uuid(),
            'id_ficha' => $ficha->uuid,
            'user_id' => $user->id,
            'descripcion' => 'Gasto de prueba',
            'ticket' => '',
            'precio' => 15.50
        ]);

        // Act
        $importe = $this->service->calcularImporte($ficha);

        // Assert
        $this->assertEquals(15.50, $importe);
    }

    /** @test */
    public function puede_calcular_importe_completo_con_productos_servicios_y_gastos()
    {
        // Arrange
        $ficha = Ficha::factory()->create();
        $producto = Producto::factory()->create(['precio' => 10.00]);
        $servicio = Servicio::factory()->create(['precio' => 5.00]);
        $user = User::factory()->create();

        // Añadir producto
        FichaProducto::create([
            'uuid' => Str::uuid(),
            'id_ficha' => $ficha->uuid,
            'id_producto' => $producto->uuid,
            'cantidad' => 1,
            'precio' => 10.00
        ]);

        // Añadir servicio
        FichaServicio::create([
            'uuid' => Str::uuid(),
            'id_ficha' => $ficha->uuid,
            'id_servicio' => $servicio->uuid,
            'precio' => 5.00
        ]);

        // Añadir gasto
        FichaGasto::create([
            'uuid' => Str::uuid(),
            'id_ficha' => $ficha->uuid,
            'user_id' => $user->id,
            'descripcion' => 'Gasto',
            'ticket' => '',
            'precio' => 3.00
        ]);

        // Act
        $importe = $this->service->calcularImporte($ficha);

        // Assert
        // 10.00 + 5.00 + 3.00 = 18.00
        $this->assertEquals(18.00, $importe);
    }

    /** @test */
    public function puede_calcular_consumos_sin_invitados()
    {
        // Arrange
        $ficha = Ficha::factory()->create();
        $producto = Producto::factory()->create(['precio' => 10.00]);
        $servicio = Servicio::factory()->create(['precio' => 5.00]);
        $user = User::factory()->create();

        FichaProducto::create([
            'uuid' => Str::uuid(),
            'id_ficha' => $ficha->uuid,
            'id_producto' => $producto->uuid,
            'cantidad' => 1,
            'precio' => 10.00
        ]);

        FichaServicio::create([
            'uuid' => Str::uuid(),
            'id_ficha' => $ficha->uuid,
            'id_servicio' => $servicio->uuid,
            'precio' => 5.00
        ]);

        FichaGasto::create([
            'uuid' => Str::uuid(),
            'id_ficha' => $ficha->uuid,
            'user_id' => $user->id,
            'descripcion' => 'Gasto',
            'ticket' => '',
            'precio' => 3.00
        ]);

        // Act
        $consumos = $this->service->calcularConsumos($ficha);

        // Assert
        // 10.00 + 5.00 + 3.00 = 18.00
        $this->assertEquals(18.00, $consumos);
    }

    /** @test */
    public function propietario_puede_ver_su_ficha()
    {
        // Arrange
        $user = User::factory()->create();
        $ficha = Ficha::factory()->create(['user_id' => $user->id]);

        // Act
        $puede = $this->service->puedeVerFicha($ficha, $user->id);

        // Assert
        $this->assertTrue($puede, 'El propietario debe poder ver su ficha');
    }

    /** @test */
    public function usuario_inscrito_puede_ver_ficha()
    {
        // Arrange
        $propietario = User::factory()->create();
        $inscrito = User::factory()->create();
        $ficha = Ficha::factory()->create(['user_id' => $propietario->id]);

        // Inscribir usuario
        FichaUsuario::create([
            'uuid' => Str::uuid(),
            'id_ficha' => $ficha->uuid,
            'user_id' => $inscrito->id,
            'invitados' => 0,
            'ninos' => 0
        ]);

        // Act
        $puede = $this->service->puedeVerFicha($ficha, $inscrito->id);

        // Assert
        $this->assertTrue($puede, 'Usuario inscrito debe poder ver la ficha');
    }

    /** @test */
    public function usuario_no_relacionado_no_puede_ver_ficha()
    {
        // Arrange
        $propietario = User::factory()->create();
        $externo = User::factory()->create();
        $ficha = Ficha::factory()->create(['user_id' => $propietario->id]);

        // Act
        $puede = $this->service->puedeVerFicha($ficha, $externo->id);

        // Assert
        $this->assertFalse($puede, 'Usuario no relacionado NO debe poder ver la ficha');
    }

    /** @test */
    public function evento_permite_inscripcion_si_hay_aforo()
    {
        // Arrange
        $ficha = Ficha::factory()->evento()->create([
            'aforo_maximo' => 10,
            'inscritos_actuales' => 5
        ]);

        // Act
        $disponibilidad = $this->service->verificarDisponibilidadInscripcion($ficha);

        // Assert
        $this->assertTrue($disponibilidad['disponible']);
        $this->assertNull($disponibilidad['razon']);
    }

    /** @test */
    public function evento_no_permite_inscripcion_si_aforo_completo()
    {
        // Arrange
        $ficha = Ficha::factory()->evento()->create([
            'aforo_maximo' => 10,
            'inscritos_actuales' => 10
        ]);

        // Act
        $disponibilidad = $this->service->verificarDisponibilidadInscripcion($ficha);

        // Assert
        $this->assertFalse($disponibilidad['disponible']);
        $this->assertEquals('Aforo completo', $disponibilidad['razon']);
    }

    /** @test */
    public function puede_inscribir_usuario_en_evento()
    {
        // Arrange
        $user = User::factory()->create();
        $ficha = Ficha::factory()->evento()->create([
            'aforo_maximo' => 10,
            'inscritos_actuales' => 0
        ]);

        // Act
        $resultado = $this->service->inscribirUsuario($ficha, $user->id);

        // Assert
        $this->assertTrue($resultado);
        
        // Verificar inscripción en BD
        $inscrito = FichaUsuario::where('id_ficha', $ficha->uuid)
            ->where('user_id', $user->id)
            ->exists();
        $this->assertTrue($inscrito);
        
        // Verificar contador incrementado
        $ficha->refresh();
        $this->assertEquals(1, $ficha->inscritos_actuales);
    }

    /** @test */
    public function no_puede_inscribir_usuario_dos_veces()
    {
        // Arrange
        $user = User::factory()->create();
        $ficha = Ficha::factory()->evento()->create([
            'aforo_maximo' => 10,
            'inscritos_actuales' => 0
        ]);

        // Primera inscripción
        $this->service->inscribirUsuario($ficha, $user->id);

        // Act - Intentar segunda inscripción
        $resultado = $this->service->inscribirUsuario($ficha, $user->id);

        // Assert
        $this->assertFalse($resultado, 'No debe permitir inscripción duplicada');
        
        // Verificar que solo hay una inscripción
        $ficha->refresh();
        $this->assertEquals(1, $ficha->inscritos_actuales);
    }

    /** @test */
    public function puede_cancelar_inscripcion()
    {
        // Arrange
        $user = User::factory()->create();
        $ficha = Ficha::factory()->evento()->create([
            'aforo_maximo' => 10,
            'inscritos_actuales' => 1
        ]);

        FichaUsuario::create([
            'uuid' => Str::uuid(),
            'id_ficha' => $ficha->uuid,
            'user_id' => $user->id,
            'invitados' => 0,
            'ninos' => 0
        ]);

        // Act
        $resultado = $this->service->cancelarInscripcion($ficha, $user->id);

        // Assert
        $this->assertTrue($resultado);
        
        // Verificar que no existe inscripción
        $inscrito = FichaUsuario::where('id_ficha', $ficha->uuid)
            ->where('user_id', $user->id)
            ->exists();
        $this->assertFalse($inscrito);
        
        // Verificar contador decrementado
        $ficha->refresh();
        $this->assertEquals(0, $ficha->inscritos_actuales);
    }
}
