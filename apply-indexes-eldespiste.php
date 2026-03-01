#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Site;

echo "🚀 Aplicando índices de rendimiento a BD: eldespiste\n\n";

try {
    // Obtener configuración del tenant eldespiste
    $site = Site::where('db_name', 'eldespiste')->first();
    
    if (!$site) {
        echo "❌ Error: No se encontró el tenant 'eldespiste'\n";
        exit(1);
    }
    
    // Configurar conexión tenant
    config(['database.connections.tenant' => [
        'driver' => 'mysql',
        'host' => $site->db_host,
        'database' => $site->db_name,
        'username' => $site->db_user,
        'password' => $site->db_password,
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ]]);
    
    DB::purge('tenant');
    DB::reconnect('tenant');
    
    echo "✅ Conectado a: " . DB::connection('tenant')->getDatabaseName() . "\n\n";
    
    // Función helper para crear índice si no existe
    function createIndexIfNotExists($table, $indexName, $columns) {
        // Verificar si todas las columnas existen
        foreach ($columns as $column) {
            $columnExists = DB::connection('tenant')
                ->select("SHOW COLUMNS FROM `{$table}` WHERE Field = ?", [$column]);
            
            if (empty($columnExists)) {
                echo "  ⚠️  Columna '{$column}' no existe en tabla {$table}, saltando índice {$indexName}\n";
                return false;
            }
        }
        
        $indexes = DB::connection('tenant')
            ->select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        
        if (empty($indexes)) {
            $columnsList = implode('`, `', $columns);
            DB::connection('tenant')->statement(
                "ALTER TABLE `{$table}` ADD INDEX `{$indexName}` (`{$columnsList}`)"
            );
            echo "  ✅ Creado índice: {$indexName} en tabla {$table}\n";
            return true;
        } else {
            echo "  ⏭️  Índice ya existe: {$indexName} en tabla {$table}\n";
            return false;
        }
    }
    
    echo "📊 Creando índices...\n\n";
    
    // Índices para fichas
    echo "Tabla: fichas\n";
    createIndexIfNotExists('fichas', 'idx_fichas_estado', ['estado']);
    createIndexIfNotExists('fichas', 'idx_fichas_fecha_hora', ['fecha', 'hora']);
    createIndexIfNotExists('fichas', 'idx_fichas_user_id', ['user_id']);
    createIndexIfNotExists('fichas', 'idx_fichas_tipo', ['tipo']);
    createIndexIfNotExists('fichas', 'idx_fichas_estado_mesa', ['estado_mesa']);
    createIndexIfNotExists('fichas', 'idx_fichas_modo', ['modo']);
    
    echo "\nTabla: fichas_productos\n";
    createIndexIfNotExists('fichas_productos', 'idx_fichas_productos_id_ficha', ['id_ficha']);
    createIndexIfNotExists('fichas_productos', 'idx_fichas_productos_id_producto', ['id_producto']);
    createIndexIfNotExists('fichas_productos', 'idx_fichas_productos_estado', ['estado']);
    
    echo "\nTabla: fichas_usuarios\n";
    createIndexIfNotExists('fichas_usuarios', 'idx_fichas_usuarios_id_ficha', ['id_ficha']);
    createIndexIfNotExists('fichas_usuarios', 'idx_fichas_usuarios_user_id', ['user_id']);
    
    echo "\nTabla: fichas_servicios\n";
    createIndexIfNotExists('fichas_servicios', 'idx_fichas_servicios_id_ficha', ['id_ficha']);
    createIndexIfNotExists('fichas_servicios', 'idx_fichas_servicios_id_servicio', ['id_servicio']);
    
    echo "\nTabla: fichas_gastos\n";
    createIndexIfNotExists('fichas_gastos', 'idx_fichas_gastos_id_ficha', ['id_ficha']);
    createIndexIfNotExists('fichas_gastos', 'idx_fichas_gastos_user_id', ['user_id']);
    
    echo "\nTabla: productos\n";
    createIndexIfNotExists('productos', 'idx_productos_familia', ['familia']);
    createIndexIfNotExists('productos', 'idx_productos_barcode', ['barcode']);
    createIndexIfNotExists('productos', 'idx_productos_combinado', ['combinado']);
    
    echo "\nTabla: composicion_productos\n";
    createIndexIfNotExists('composicion_productos', 'idx_composicion_productos_id_producto', ['id_producto']);
    createIndexIfNotExists('composicion_productos', 'idx_composicion_productos_id_componente', ['id_componente']);
    
    echo "\n🎉 ¡Índices aplicados correctamente!\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
