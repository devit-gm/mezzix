<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Obtener el idioma por defecto de la aplicación
        $locale = config('app.locale', 'es');

        // Si hay un sitio activo, usar su idioma
        $site = get_site();
        if ($site && $site->locale) {
            $locale = $site->locale;
        }

        // Si hay un usuario autenticado y tiene un idioma configurado, tiene prioridad
        if (Auth::check()) {
            $user = Auth::user();
            if ($user && $user->locale) {
                $locale = $user->locale;
            }
        }

        // Establecer el idioma de la aplicación
        App::setLocale($locale);

        return $next($request);
    }
}
