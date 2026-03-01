<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Ajustes;

class CacheAjustes
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $site = get_site();
        
        // Cachear ajustes por 60 minutos usando el site_id como clave
        $ajustes = Cache::remember('ajustes_site_' . $site->id, 3600, function () {
            return Ajustes::first();
        });
        
        // Compartir ajustes con todas las vistas
        view()->share('ajustes', $ajustes);
        
        // También disponible vía app()
        app()->instance('ajustes', $ajustes);
        
        return $next($request);
    }
}
