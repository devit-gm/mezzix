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
        // 🚀 OPTIMIZACIÓN: Saltar configuración de site en comandos CLI y rutas públicas
        if ($this->app->runningInConsole()) {
            return;
        }

        // Saltar en rutas de autenticación para evitar 500 antes de login
        if ($request->is('login', 'register', 'password/*', 'logout')) {
            return;
        }

        $domain = $request->getHost();

        try {
            // Usar el helper cacheado en lugar de una query directa
            $site = get_site();

            if (!$site) {
                // Solo abortar si no es una ruta API
                if (!$request->is('api/*')) {
                    abort(404, 'Site not found.');
                }
                return;
            }

            app()->instance('site', $site);

            // Cargar ajustes del sitio con CACHE y registrarlos globalmente
            $ajustes = \Cache::remember("ajustes_site_{$site->uuid}", 3600, function () {
                return DB::connection('site')->table('ajustes')->first();
            });

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
        } catch (\Exception $e) {
            // 🚨 Loggear errores pero no romper la app
            Log::error('Error en AppServiceProvider::boot', [
                'message' => $e->getMessage(),
                'domain' => $domain,
                'url' => $request->fullUrl()
            ]);

            // Si es request web, mostrar error amigable
            if (!$request->is('api/*')) {
                abort(500, 'Error al cargar configuración del sitio');
            }
        }
    }

    /**
     * Configurar eager loading por defecto en modelos críticos
     * Solo se ejecuta una vez usando una flag estática para evitar overhead por request
     */
    protected function configureEagerLoading()
    {
        static $configured = false;
        if ($configured) {
            return;
        }
        $configured = true;

        $isNotProduction = !app()->isProduction();
        FichaProducto::preventLazyLoading($isNotProduction);
        Producto::preventLazyLoading($isNotProduction);
        ComposicionProducto::preventLazyLoading($isNotProduction);
    }

    protected function defineSiteRoutes($site)
    {
        $siteId = $site->id;

        Route::domain($site->dominio)->group(function () use ($siteId) {
            Route::get('/', 'App\Http\Controllers\SitiosController@show')->name("site{$siteId}.home");
        });
    }
}
