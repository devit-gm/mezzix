<?php

namespace App\Providers;

use App\Models\License;
use Illuminate\Support\ServiceProvider;
use App\Models\Site;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use App\Models\FichaProducto;
use App\Models\Producto;
use App\Models\ComposicionProducto;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(Request $request): void
    {
        $domain = $request->getHost();



        $site = Site::where('dominio', $domain)->first();

        if (!$site) {
            abort(404, 'Site not found.');
        }

        app()->instance('site', $site);

        // Cargar ajustes del sitio y registrarlos globalmente
        $ajustes = DB::connection('site')->table('ajustes')->first();
        if ($ajustes) {
            app()->instance('ajustes', $ajustes);
        }

        config(['database.connections.site' => [
            'driver' => 'mysql',
            'host' => $site->db_host,
            'database' => $site->db_name,
            'username' => $site->db_user,
            'password' => $site->db_password,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
        ]]);

        // Configurar paths específicos del sitio
        config(['site.logo' => $site->ruta_logo]);
        config(['site.name' => $site->nombre]);
        config(['site.logoNav' => $site->ruta_logo_nav]);
        config(['site.styles' => $site->ruta_estilos]);
        config(['site.favicon' => $site->favicon]);

        $this->defineSiteRoutes($site);
        
        // OPTIMIZADO: Eager loading global para prevenir N+1 queries
        $this->configureEagerLoading();
    }
    
    /**
     * Configurar eager loading por defecto en modelos críticos
     */
    protected function configureEagerLoading()
    {
        // FichaProducto siempre carga su producto relacionado
        FichaProducto::preventLazyLoading(!app()->isProduction());
        
        // Producto siempre carga composición cuando se accede
        Producto::preventLazyLoading(!app()->isProduction());
        
        // ComposicionProducto siempre carga el componente
        ComposicionProducto::preventLazyLoading(!app()->isProduction());
        
        Log::info('Eager loading global configurado para prevenir N+1 queries');
    }

    protected function defineSiteRoutes($site)
    {
        $siteId = $site->id;

        Route::domain($site->dominio)->group(function () use ($siteId) {
            Route::get('/', 'App\Http\Controllers\SitiosController@show')->name("site{$siteId}.home");
        });
    }
}
