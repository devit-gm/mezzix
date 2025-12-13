<?php

use App\Http\Controllers\AjustesController;
use App\Http\Controllers\FamiliasController;
use App\Http\Controllers\ProductosController;
use App\Http\Controllers\ProveedoresController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ServiciosController;
use App\Http\Controllers\UsuariosController;
use App\Http\Controllers\ReservasController;
use App\Http\Controllers\FichasController;
use App\Http\Controllers\InformesController;
use App\Http\Controllers\FacturacionController;
use App\Http\Controllers\FacturaMesaController;
use App\Http\Controllers\LicenciasController;
use App\Http\Controllers\SitiosController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\ContactoController;
use App\Http\Controllers\ManifestController;
use App\Http\Controllers\NotificationController;
use App\Http\Middleware\RoleMiddleware;
use App\Services\FirebaseService;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

//Auth::routes();

// Rutas públicas para PWA
Route::get('/manifest.json', [ManifestController::class, 'show'])->name('manifest');

// Ruta protegida para ejecución de cron desde IONOS
Route::get('/cron/reservas-verificar/{token}', function($token) {
    if ($token !== env('CRON_SECRET')) {
        abort(403);
    }
    \Artisan::call('reservas:verificar-proximas');
    return 'OK';
});

Route::middleware(['detect.site', 'auth'])->group(function () {
    // Ruta raíz dinámica según modo de operación
    Route::get('/', function () {
        try {
            $ajustes = \App\Models\Ajustes::first();
            $modoOperacion = $ajustes->modo_operacion ?? 'fichas';
        } catch (\Exception $e) {
            \Log::warning('Tabla ajustes no encontrada en ruta raíz', ['error' => $e->getMessage()]);
            $modoOperacion = 'fichas';
        }
        
        if ($modoOperacion === 'mesas') {
            return redirect()->route('mesas.index');
        }
        
        return app(FichasController::class)->index();
    })->name('home');
    
    Route::get('/home', function () {
        try {
            $ajustes = \App\Models\Ajustes::first();
            $modoOperacion = $ajustes->modo_operacion ?? 'fichas';
        } catch (\Exception $e) {
            \Log::warning('Tabla ajustes no encontrada en ruta /home', ['error' => $e->getMessage()]);
            $modoOperacion = 'fichas';
        }
        
        if ($modoOperacion === 'mesas') {
            return redirect()->route('mesas.index');
        }
        
        return app(FichasController::class)->index();
    });

    Route::get('/usuarios', UsuariosController::class . '@index')->name('usuarios.index');
    Route::get('/usuarios/create', UsuariosController::class . '@create')->name('usuarios.create');
    Route::post('/usuarios', UsuariosController::class . '@store')->name('usuarios.store');
    Route::get('/usuarios/{usuario}', UsuariosController::class . '@show')->name('usuarios.show');
    Route::get('/usuarios/{usuario}/edit', UsuariosController::class . '@edit')->name('usuarios.edit');
    Route::get('/usuarios/{usuario}/view', UsuariosController::class . '@view')->name('usuarios.view');
    Route::put('/usuarios/{usuario}', UsuariosController::class . '@update')->name('usuarios.update');
    Route::delete('/usuarios/{usuario}', UsuariosController::class . '@destroy')->name('usuarios.destroy');

    Route::get('/familias', FamiliasController::class . '@index')->name('familias.index');
    Route::get('/familias/create', FamiliasController::class . '@create')->name('familias.create');
    Route::post('/familias', FamiliasController::class . '@store')->name('familias.store');
    Route::get('/familias/{uuid}', FamiliasController::class . '@show')->name('familias.show');
    Route::get('/familias/{uuid}/edit', FamiliasController::class . '@edit')->name('familias.edit');
    Route::get('/familias/{uuid}/view', FamiliasController::class . '@view')->name('familias.view');
    Route::put('/familias/{uuid}', FamiliasController::class . '@update')->name('familias.update');
    Route::delete('/familias/{uuid}', FamiliasController::class . '@destroy')->name('familias.destroy');

    Route::get('/productos', ProductosController::class . '@index')->name('productos.index');
    Route::get('/productos/create', ProductosController::class . '@create')->name('productos.create');
    Route::post('/productos', ProductosController::class . '@store')->name('productos.store');
    Route::get('/productos/{uuid}/edit', ProductosController::class . '@edit')->name('productos.edit');
    Route::get('/productos/{uuid}/list', ProductosController::class . '@list')->name('productos.list');
    Route::get('/productos/{uuid}/components', ProductosController::class . '@components')->name('productos.components');
    Route::put('/productos/{uuid}/update', ProductosController::class . '@update')->name('productos.update');
    Route::put('/productos/{uuid}/update_components', ProductosController::class . '@update_components')->name('productos.update_components');
    Route::delete('/productos/{uuid}', ProductosController::class . '@destroy')->name('productos.destroy');
    Route::get('/productos/inventory', ProductosController::class . '@inventory')->name('productos.inventory');
    Route::put('/productos/inventory', ProductosController::class . '@inventory')->name('productos.inventory');
    Route::post('/productos/buscar-barcode', [ProductosController::class, 'buscarPorBarcode'])->name('productos.buscar.barcode');

    Route::get('/fichas', FichasController::class . '@index')->name('fichas.index');
    Route::put('/fichas', FichasController::class . '@index')->name('fichas.index');
    Route::get('/fichas/create', FichasController::class . '@create')->name('fichas.create');
    Route::post('/fichas', FichasController::class . '@store')->name('fichas.store');
    Route::get('/fichas/{uuid}/edit', FichasController::class . '@edit')->name('fichas.edit');
    Route::get('/fichas/{uuid}/download', FichasController::class . '@download')->name('fichas.download');
    Route::get('/fichas/{uuid}/familias', FichasController::class . '@familias')->name('fichas.familias');
    Route::get('/fichas/{uuid}', FichasController::class . '@show')->name('fichas.show');
    Route::get('fichas/{uuid}/familias/{uuid2}/productos', FichasController::class . '@productos')->name('fichas.productos');
    Route::post('fichas/{uuid}/familias/{uuid2}/productos', FichasController::class . '@addproduct')->name('fichas.addproduct');
    Route::put('/fichas/{uuid}', FichasController::class . '@update')->name('fichas.update');
    Route::delete('/fichas/{uuid}', FichasController::class . '@destroy')->name('fichas.destroy');
    Route::get('/fichas/{uuid}/lista', FichasController::class . '@lista')->name('fichas.lista');
    Route::delete('/fichas/{uuid}/lista/{uuid2}', FichasController::class . '@destroylista')->name('fichas.destroylista');
    Route::put('/fichas/{uuid}/lista/{uuid2}/{cantidad}', FichasController::class . '@updatelista')->name('fichas.updatelista');
    Route::post('/fichas/buscar-barcode', [FichasController::class, 'buscarPorBarcode'])->name('fichas.buscar.barcode');
    Route::get('/fichas/{uuid}/usuarios', FichasController::class . '@usuarios')->name('fichas.usuarios');
    Route::put('/fichas/{uuid}/usuarios', FichasController::class . '@updateusuarios')->name('fichas.updateusuarios');
    Route::get('/fichas/{uuid}/servicios', FichasController::class . '@servicios')->name('fichas.servicios');
    Route::put('/fichas/{uuid}/servicios', FichasController::class . '@updateservicios')->name('fichas.updateservicios');
    Route::get('/fichas/{uuid}/gastos', FichasController::class . '@gastos')->name('fichas.gastos');
    Route::get('/fichas/{uuid}/gastos/add', FichasController::class . '@addgastos')->name('fichas.addgastos');
    Route::put('/fichas/{uuid}/gastos', FichasController::class . '@updategastos')->name('fichas.updategastos');
    Route::delete('/fichas/{uuid}/gastos/{uuid2}', FichasController::class . '@destroygastos')->name('fichas.destroygastos');
    Route::get('/fichas/{uuid}/resumen', FichasController::class . '@resumen')->name('fichas.resumen');
    Route::put('/fichas/{uuid}/resumen', FichasController::class . '@enviar')->name('fichas.enviar');
    Route::post('/fichas/{mesaId}/abrir', [FichasController::class, 'abrirMesa'])->name('fichas.abrir');
    Route::post('/fichas/{mesaId}/tomar', [FichasController::class, 'tomarMesa'])->name('fichas.tomar');
    Route::post('/fichas/{mesaId}/cerrar', [FichasController::class, 'cerrarMesa'])->name('fichas.cerrar');
    Route::post('/fichas/{mesaId}/liberar', [FichasController::class, 'liberarMesa'])->name('fichas.liberar');
    Route::get('/fichas/{uuid}/enviar-cocina', [FichasController::class, 'enviarCocina'])->name('fichas.enviarCocina');

    // Rutas para sistema de mesas
    Route::get('/mesas', [FichasController::class, 'indexMesas'])->name('mesas.index');
    Route::post('/mesas/generar', [FichasController::class, 'generarMesas'])->name('mesas.generar');
    Route::post('/mesas/crear-individual', [FichasController::class, 'crearMesaIndividual'])->name('mesas.crear-individual');
    Route::put('/mesas/{mesaUuid}/actualizar', [FichasController::class, 'actualizarMesa'])->name('mesas.actualizar');
    Route::delete('/mesas/{mesaUuid}', [FichasController::class, 'eliminarMesa'])->name('mesas.eliminar');
    Route::post('/mesas/reordenar', [FichasController::class, 'reordenarMesas'])->name('mesas.reordenar');
    Route::post('/mesas/{mesaId}/abrir', [FichasController::class, 'abrirMesa'])->name('mesas.abrir');
    Route::post('/mesas/{mesaId}/tomar', [FichasController::class, 'tomarMesa'])->name('mesas.tomar');
    Route::get('/mesas/{mesaId}/resumen', [FichasController::class, 'resumenMesa'])->name('mesas.resumen');
    Route::post('/mesas/{mesaId}/cerrar', [FichasController::class, 'cerrarMesa'])->name('mesas.cerrar');
    Route::post('/mesas/{mesaId}/liberar', [FichasController::class, 'liberarMesa'])->name('mesas.liberar');
    
    // Rutas alias para mesas (apuntan al mismo controlador que fichas)
    Route::get('/mesas/{uuid}/lista', [FichasController::class, 'lista'])->name('mesas.lista');
    Route::get('/mesas/{uuid}/familias', [FichasController::class, 'familias'])->name('mesas.familias');
    Route::get('/mesas/{uuid}/familias/{uuid2}/productos', [FichasController::class, 'productos'])->name('mesas.productos');
    Route::post('/mesas/{uuid}/familias/{uuid2}/productos', [FichasController::class, 'addproduct'])->name('mesas.addproduct');
    Route::delete('/mesas/{uuid}/lista/{uuid2}', [FichasController::class, 'destroylista'])->name('mesas.destroylista');
    Route::put('/mesas/{uuid}/lista/{uuid2}/{cantidad}', [FichasController::class, 'updatelista'])->name('mesas.updatelista');
    Route::post('/mesas/buscar-barcode', [FichasController::class, 'buscarPorBarcode'])->name('mesas.buscar.barcode');
    Route::get('/mesas/{uuid}/usuarios', [FichasController::class, 'usuarios'])->name('mesas.usuarios');
    Route::put('/mesas/{uuid}/usuarios', [FichasController::class, 'updateusuarios'])->name('mesas.updateusuarios');
    Route::get('/mesas/{uuid}/servicios', [FichasController::class, 'servicios'])->name('mesas.servicios');
    Route::put('/mesas/{uuid}/servicios', [FichasController::class, 'updateservicios'])->name('mesas.updateservicios');
    Route::get('/mesas/{uuid}/gastos', [FichasController::class, 'gastos'])->name('mesas.gastos');
    Route::get('/mesas/{uuid}/gastos/add', [FichasController::class, 'addgastos'])->name('mesas.addgastos');
    Route::put('/mesas/{uuid}/gastos', [FichasController::class, 'updategastos'])->name('mesas.updategastos');
    Route::delete('/mesas/{uuid}/gastos/{uuid2}', [FichasController::class, 'destroygastos'])->name('mesas.destroygastos');
    Route::get('/mesas/{uuid}/resumen-final', [FichasController::class, 'resumen'])->name('mesas.resumen-final');
    Route::put('/mesas/{uuid}/resumen-final', [FichasController::class, 'enviar'])->name('mesas.enviar');

    // Vista de cocina para mesas
    Route::get('/cocina/mesas', [\App\Http\Controllers\CocinaMesasController::class, 'index'])->name('cocina.mesas')->middleware(['auth']);
    // Actualizar datos de cocina (AJAX)
    Route::get('/cocina/mesas/actualizar', [\App\Http\Controllers\CocinaMesasController::class, 'actualizar'])->name('cocina.mesas.actualizar')->middleware(['auth']);
    // Marcar producto como preparado (POST JSON)
    Route::post('/cocina/mesas/preparar', [\App\Http\Controllers\CocinaMesasController::class, 'preparar'])->name('cocina.mesas.preparar')->middleware(['auth']);


    Route::get('/informes', [InformesController::class, 'index'])->name('informes.index');
    Route::put('/informes', [InformesController::class, 'index'])->name('informes.balance');
    Route::put('/informes/facturar', [InformesController::class, 'facturar'])->name('informes.facturar');
    
    // Informes para modo mesas
    Route::get('/informes/ventas-productos', [InformesController::class, 'informeProductos'])->name('informes.ventas-productos');
    Route::get('/informes/ventas-camareros', [InformesController::class, 'ventasCamareros'])->name('informes.ventas-camareros');
    Route::get('/informes/ocupacion-mesas', [InformesController::class, 'ocupacionMesas'])->name('informes.ocupacion-mesas');
    Route::get('/informes/horas-pico', [InformesController::class, 'horasPico'])->name('informes.horas-pico');
    
    // Informes para modo fichas
    Route::get('/informes/ventas-productos-fichas', [InformesController::class, 'ventasProductosFichas'])->name('informes.ventas-productos-fichas');
    Route::get('/informes/ventas-socios', [InformesController::class, 'ventasSocios'])->name('informes.ventas-socios');
    Route::get('/informes/compras-usuarios', [InformesController::class, 'comprasUsuarios'])->name('informes.compras-usuarios');
    Route::get('/informes/evolucion-temporal', [InformesController::class, 'evolucionTemporal'])->name('informes.evolucion-temporal');

    Route::get('/facturacion', [FacturacionController::class, 'index'])->name('facturacion.index');
    
    // Facturas de mesas
    Route::get('/facturas', [FacturaMesaController::class, 'index'])->name('facturas.index');
    Route::get('/facturas/crear/{mesaId}', [FacturaMesaController::class, 'crear'])->name('facturas.crear');
    Route::post('/facturas/{mesaId}', [FacturaMesaController::class, 'store'])->name('facturas.store');
    Route::get('/facturas/{id}/show', [FacturaMesaController::class, 'show'])->name('facturas.show');
    Route::get('/facturas/{id}/pdf', [FacturaMesaController::class, 'pdf'])->name('facturas.pdf');
    
    // Ticket de mesa
    Route::get('/mesas/{mesaId}/ticket', [FichasController::class, 'generarTicket'])->name('mesas.ticket');
    
    // Ticket de ficha (descarga PDF)
    Route::get('/fichas/{uuid}/ticket', [FichasController::class, 'descargarTicket'])->name('fichas.ticket');

    // Albaranes de compra
    Route::get('/albaranes', [\App\Http\Controllers\AlbaranesController::class, 'index'])->name('albaranes.index');
    Route::get('/albaranes/create', [\App\Http\Controllers\AlbaranesController::class, 'create'])->name('albaranes.create');
    Route::post('/albaranes', [\App\Http\Controllers\AlbaranesController::class, 'store'])->name('albaranes.store');
    Route::get('/albaranes/{id}', [\App\Http\Controllers\AlbaranesController::class, 'show'])->name('albaranes.show');
    Route::get('/albaranes/{id}/edit', [\App\Http\Controllers\AlbaranesController::class, 'edit'])->name('albaranes.edit');
    Route::put('/albaranes/{id}', [\App\Http\Controllers\AlbaranesController::class, 'update'])->name('albaranes.update');
    Route::delete('/albaranes/{id}', [\App\Http\Controllers\AlbaranesController::class, 'destroy'])->name('albaranes.destroy');
    Route::post('/albaranes/{id}/confirmar', [\App\Http\Controllers\AlbaranesController::class, 'confirmarRecepcion'])->name('albaranes.confirmar');
    Route::get('/albaranes/{id}/pdf', [\App\Http\Controllers\AlbaranesController::class, 'pdf'])->name('albaranes.pdf');

    // Proveedores
    Route::get('/proveedores', [ProveedoresController::class, 'index'])->name('proveedores.index');
    Route::get('/proveedores/create', [ProveedoresController::class, 'create'])->name('proveedores.create');
    Route::post('/proveedores', [ProveedoresController::class, 'store'])->name('proveedores.store');
    Route::get('/proveedores/{uuid}', [ProveedoresController::class, 'show'])->name('proveedores.show');
    Route::get('/proveedores/{uuid}/edit', [ProveedoresController::class, 'edit'])->name('proveedores.edit');
    Route::put('/proveedores/{uuid}', [ProveedoresController::class, 'update'])->name('proveedores.update');
    Route::delete('/proveedores/{uuid}', [ProveedoresController::class, 'destroy'])->name('proveedores.destroy');
    Route::post('/proveedores/{uuid}/toggle', [ProveedoresController::class, 'toggleActivo'])->name('proveedores.toggle');
    Route::get('/proveedores-estadisticas', [ProveedoresController::class, 'estadisticas'])->name('proveedores.estadisticas');

    Route::get('/servicios', ServiciosController::class . '@index')->name('servicios.index');
    Route::get('/servicios/create', ServiciosController::class . '@create')->name('servicios.create');
    Route::post('/servicios', ServiciosController::class . '@store')->name('servicios.store');
    Route::get('/servicios/{uuid}', ServiciosController::class . '@show')->name('servicios.show');
    Route::get('/servicios/{uuid}/edit', ServiciosController::class . '@edit')->name('servicios.edit');
    Route::get('/servicios/{uuid}/view', ServiciosController::class . '@view')->name('servicios.view');
    Route::put('/servicios/{uuid}', ServiciosController::class . '@update')->name('servicios.update');
    Route::delete('/servicios/{uuid}', ServiciosController::class . '@destroy')->name('servicios.destroy');

    Route::get('/reservas', [ReservasController::class, 'index'])->name('reservas.index');
    Route::get('/reservas/calendario', [ReservasController::class, 'calendario'])->name('reservas.calendario');
    Route::get('/reservas/create', [ReservasController::class, 'create'])->name('reservas.create');
    Route::post('/reservas', [ReservasController::class, 'store'])->name('reservas.store');
    Route::delete('/reservas/{uuid}', ReservasController::class . '@destroy')->name('reservas.destroy');
    Route::get('/reservas/{uuid}/edit', ReservasController::class . '@edit')->name('reservas.edit');
    Route::put('/reservas/{uuid}', ReservasController::class . '@update')->name('reservas.update');

    Route::get('/ajustes', [AjustesController::class, 'index'])->name('ajustes.index');
    Route::put('/ajustes', [AjustesController::class, 'update'])->name('ajustes.update');

    Route::get('/sitios', [SitiosController::class, 'index'])->name('sitios.index');
    Route::get('/sitios/create', [SitiosController::class, 'create'])->name('sitios.create');
    Route::get('/sitios/{id}/edit', [SitiosController::class, 'edit'])->name('sitios.edit');
    Route::put('/sitios/{id}/update', [SitiosController::class, 'update'])->name('sitios.update');
    Route::delete('/sitios/{id}', [SitiosController::class, 'destroy'])->name('sitios.destroy');

    Route::get('/licencias', [LicenciasController::class, 'index'])->name('licencias.index');
    Route::put('/licencias', [LicenciasController::class, 'index'])->name('licencias.index');
    Route::get('/licencias/create', [LicenciasController::class, 'create'])->name('licencias.create');
    Route::get('/licencias/{id}/edit', [LicenciasController::class, 'edit'])->name('licencias.edit');
    Route::put('/licencias/{id}/update', [LicenciasController::class, 'update'])->name('licencias.update');
    Route::delete('/licencias/{id}', [LicenciasController::class, 'destroy'])->name('licencias.destroy');
    Route::get('/licencias/error', [LicenciasController::class, 'error'])->name('licencias.error');
    Route::put('/licencias/error', [LicenciasController::class, 'error'])->name('licencias.error');

    Route::post('/send-sms', [SmsController::class, 'sendSms'])->name('sms.enviar');
    
    Route::get('/contacto', [ContactoController::class, 'index'])->name('contacto.index');
    Route::post('/contacto', [ContactoController::class, 'send'])->name('contacto.send');
    Route::post('/save-fcm-token', [NotificationController::class, 'saveToken'])->middleware('auth');
    Route::post('/enviar-notificacion-global', [NotificationController::class, 'enviarNotificacionGlobal'])->middleware('auth');
    
    // Ruta temporal de prueba para Firebase (requiere vendor/kreait en servidor)
    /*
    Route::get('/test-firebase', function (FirebaseService $firebase) {
        try {
            $user = auth()->user();
            
            if (!$user->fcm_token) {
                return response()->json([
                    'error' => 'No tienes un token FCM registrado. Asegúrate de que el navegador haya solicitado permisos de notificación.'
                ], 400);
            }
            
            $result = $firebase->sendNotification(
                $user->fcm_token,
                'Prueba de Firebase',
                'Si recibes esta notificación, ¡Firebase está configurado correctamente!',
                ['type' => 'test', 'timestamp' => now()->toIso8601String()]
            );
            
            return response()->json([
                'success' => $result,
                'message' => $result ? 'Notificación enviada correctamente' : 'Error al enviar notificación',
                'token' => substr($user->fcm_token, 0, 20) . '...'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error en la configuración de Firebase',
                'message' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    });
    */
    
    // Ruta para verificar token FCM
    Route::get('/check-fcm-token', function() {
        $user = auth()->user();
        return response()->json([
            'user_id' => $user->id,
            'name' => $user->name,
            'has_token' => !empty($user->fcm_token),
            'token_preview' => $user->fcm_token ? substr($user->fcm_token, 0, 30) . '...' : null,
            'token_length' => $user->fcm_token ? strlen($user->fcm_token) : 0
        ]);
    });
    
    // Ruta temporal para limpiar caché (eliminar después de usar)
    Route::get('/clear-cache-temp', function() {
        \Artisan::call('config:clear');
        \Artisan::call('cache:clear');
        \Artisan::call('route:clear');
        \Artisan::call('view:clear');
        
        return response()->json([
            'success' => true,
            'message' => 'Caché limpiada correctamente'
        ]);
    })->middleware('auth');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
