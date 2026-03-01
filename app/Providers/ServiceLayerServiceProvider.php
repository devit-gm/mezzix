<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\FichaService;
use App\Services\MesaService;
use App\Services\ProductoService;

class ServiceLayerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Registrar FichaService como singleton
        $this->app->singleton(FichaService::class, function ($app) {
            return new FichaService();
        });
        
        // Registrar MesaService como singleton (depende de FichaService)
        $this->app->singleton(MesaService::class, function ($app) {
            return new MesaService($app->make(FichaService::class));
        });
        
        // Registrar ProductoService como singleton
        $this->app->singleton(ProductoService::class, function ($app) {
            return new ProductoService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
