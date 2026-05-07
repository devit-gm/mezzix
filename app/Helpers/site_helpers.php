<?php

if (!function_exists('get_site')) {
    /**
     * Obtener site actual de forma segura.
     * Se almacena en el IoC container (por request) y en caché de archivo
     * (entre requests) para evitar consultas repetidas a la BD central.
     *
     * @return \App\Models\Site|null
     */
    function get_site()
    {
        // 1. Ya resuelto en este request: cero coste
        if (app()->bound('site')) {
            return app('site');
        }

        try {
            $domain = request()->getHost();

            // Normalizar con/sin www
            $domainWithWww    = str_starts_with($domain, 'www.') ? $domain : 'www.' . $domain;
            $domainWithoutWww = str_starts_with($domain, 'www.') ? substr($domain, 4) : $domain;
            $cacheKey = 'site_domain_' . md5($domain);

            // 2. Intentar leer del caché (evita query a BD central)
            $siteData = \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($domainWithWww, $domainWithoutWww, $domain) {
                return \App\Models\Site::where('dominio', $domainWithWww)
                    ->orWhere('dominio', $domainWithoutWww)
                    ->orWhere('dominio', $domain)
                    ->first();
            });

            if ($siteData) {
                app()->instance('site', $siteData);
                return $siteData;
            }
        } catch (\Exception $e) {
            \Log::error('Error al obtener site en helper', [
                'message' => $e->getMessage(),
                'domain'  => request()->getHost(),
            ]);
        }

        return null;
    }
}

if (!function_exists('get_ajustes')) {
    /**
     * Obtener ajustes del site actual de forma segura
     * 
     * @return object|null
     */
    function get_ajustes()
    {
        // Si ya está en el container, devolverlo
        if (app()->bound('ajustes')) {
            return app('ajustes');
        }

        // Si no, intentar cargar
        try {
            $site = get_site();

            if (!$site) {
                return null;
            }

            $ajustes = \Cache::remember("ajustes_site_{$site->uuid}", 3600, function () {
                return \DB::connection('site')->table('ajustes')->first();
            });

            if ($ajustes) {
                app()->instance('ajustes', $ajustes);
            }

            return $ajustes;
        } catch (\Exception $e) {
            \Log::error('Error al obtener ajustes en helper', [
                'message' => $e->getMessage()
            ]);
        }

        return null;
    }
}
