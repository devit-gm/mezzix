<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Producto;
use App\Models\Ficha;
use App\Models\FichaProducto;
use Illuminate\Support\Facades\DB;

class RecalcularStockReservado extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:recalcular-reservado';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalcula el stock reservado de todos los productos basándose en fichas abiertas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando recálculo de stock reservado...');
        
        // Resetear todo el stock reservado a 0
        DB::connection('site')->table('productos')->update(['stock_reservado' => 0]);
        $this->info('Stock reservado reseteado a 0');
        
        // Obtener todas las fichas abiertas (estado = 0)
        $fichasAbiertas = Ficha::where('estado', 0)->pluck('uuid');
        $this->info("Fichas abiertas encontradas: {$fichasAbiertas->count()}");
        
        if ($fichasAbiertas->isEmpty()) {
            $this->info('No hay fichas abiertas. Proceso completado.');
            return 0;
        }
        
        // Obtener todos los productos de fichas abiertas
        $productosFichas = FichaProducto::whereIn('id_ficha', $fichasAbiertas)
            ->select('id_producto', DB::raw('SUM(cantidad) as total_reservado'))
            ->groupBy('id_producto')
            ->get();
        
        $this->info("Productos en fichas abiertas: {$productosFichas->count()}");
        
        $actualizados = 0;
        $bar = $this->output->createProgressBar($productosFichas->count());
        
        foreach ($productosFichas as $productoFicha) {
            $producto = Producto::find($productoFicha->id_producto);
            
            if ($producto) {
                if ($producto->combinado == 1) {
                    // Producto combinado: reservar stock de componentes
                    $componentes = DB::connection('site')
                        ->table('composicion_productos')
                        ->where('id_producto', $producto->uuid)
                        ->get();
                    
                    foreach ($componentes as $comp) {
                        $componente = Producto::find($comp->id_componente);
                        if ($componente) {
                            $componente->increment('stock_reservado', $productoFicha->total_reservado);
                        }
                    }
                } else {
                    // Producto simple: reservar stock directamente
                    $producto->increment('stock_reservado', $productoFicha->total_reservado);
                }
                $actualizados++;
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info("Proceso completado. {$actualizados} productos actualizados.");
        
        // Mostrar resumen
        $productosConReserva = Producto::where('stock_reservado', '>', 0)->count();
        $this->info("Productos con stock reservado: {$productosConReserva}");
        
        return 0;
    }
}
