<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Site;

class MigrateIndexesToTenants extends Command
{
    protected $signature = 'tenants:migrate-indexes';
    protected $description = 'Aplicar migration de índices de rendimiento a todas las bases de datos tenant';

    public function handle()
    {
        $this->info('🚀 Aplicando índices de rendimiento a todos los tenants...');
        
        $sites = Site::all();
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($sites as $site) {
            $this->line("Procesando: {$site->nombre} ({$site->db_name})...");
            
            try {
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
                
                // Ejecutar migration manualmente en este tenant
                $this->createIndexes();
                
                $this->info("  ✅ Índices aplicados correctamente");
                $successCount++;
                
            } catch (\Exception $e) {
                $this->error("  ❌ Error: " . $e->getMessage());
                $errorCount++;
            }
        }
        
        $this->newLine();
        $this->info("✅ Completado: {$successCount} tenants correctos, {$errorCount} errores");
        
        return 0;
    }
    
    protected function createIndexes()
    {
        // Índices para fichas
        $this->createIndexIfNotExists('fichas', 'idx_fichas_estado', ['estado']);
        $this->createIndexIfNotExists('fichas', 'idx_fichas_fecha_hora', ['fecha', 'hora']);
        $this->createIndexIfNotExists('fichas', 'idx_fichas_user_id', ['user_id']);
        $this->createIndexIfNotExists('fichas', 'idx_fichas_tipo', ['tipo']);
        $this->createIndexIfNotExists('fichas', 'idx_fichas_estado_mesa', ['estado_mesa']);
        $this->createIndexIfNotExists('fichas', 'idx_fichas_modo', ['modo']);
        
        // Índices para ficha_productos
        $this->createIndexIfNotExists('ficha_productos', 'idx_ficha_productos_id_ficha', ['id_ficha']);
        $this->createIndexIfNotExists('ficha_productos', 'idx_ficha_productos_id_producto', ['id_producto']);
        $this->createIndexIfNotExists('ficha_productos', 'idx_ficha_productos_estado', ['estado']);
        
        // Índices para fichas_usuarios
        $this->createIndexIfNotExists('fichas_usuarios', 'idx_fichas_usuarios_id_ficha', ['id_ficha']);
        $this->createIndexIfNotExists('fichas_usuarios', 'idx_fichas_usuarios_user_id', ['user_id']);
        
        // Índices para ficha_servicios
        $this->createIndexIfNotExists('ficha_servicios', 'idx_ficha_servicios_id_ficha', ['id_ficha']);
        $this->createIndexIfNotExists('ficha_servicios', 'idx_ficha_servicios_id_servicio', ['id_servicio']);
        
        // Índices para ficha_gastos
        $this->createIndexIfNotExists('ficha_gastos', 'idx_ficha_gastos_id_ficha', ['id_ficha']);
        $this->createIndexIfNotExists('ficha_gastos', 'idx_ficha_gastos_user_id', ['user_id']);
        
        // Índices para productos
        $this->createIndexIfNotExists('productos', 'idx_productos_familia', ['familia']);
        $this->createIndexIfNotExists('productos', 'idx_productos_barcode', ['barcode']);
        $this->createIndexIfNotExists('productos', 'idx_productos_combinado', ['combinado']);
        
        // Índices para composicion_productos
        $this->createIndexIfNotExists('composicion_productos', 'idx_composicion_productos_id_producto', ['id_producto']);
        $this->createIndexIfNotExists('composicion_productos', 'idx_composicion_productos_id_componente', ['id_componente']);
    }
    
    protected function createIndexIfNotExists($table, $indexName, $columns)
    {
        // Verificar si el índice ya existe
        $indexes = DB::connection('tenant')
            ->select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        
        if (empty($indexes)) {
            $columnsList = implode('`, `', $columns);
            DB::connection('tenant')->statement(
                "ALTER TABLE `{$table}` ADD INDEX `{$indexName}` (`{$columnsList}`)"
            );
        }
    }
}
