<?php

if (!function_exists('get_site')) {
    /**
     * Obtener site actual de forma segura
     * Si no está cargado, lo carga desde el dominio actual
     * 
     * @return \App\Models\Site|null
     */
    function get_site()
    {
        // Si ya está en el container, devolverlo
        if (app()->bound('site')) {
            return app('site');
        }
        
        // Si no, cargar desde el request actual
        try {
            $domain = request()->getHost();
            
            // Normalizar el dominio con/sin www
            $domainWithWww = str_starts_with($domain, 'www.') ? $domain : 'www.' . $domain;
            $domainWithoutWww = str_starts_with($domain, 'www.') ? substr($domain, 4) : $domain;
            
            // Buscar el sitio con o sin www
            $site = \App\Models\Site::where('dominio', $domainWithWww)
                ->orWhere('dominio', $domainWithoutWww)
                ->orWhere('dominio', $domain)
                ->first();
            
            if ($site) {
                // Guardar en container para próximas llamadas
                app()->instance('site', $site);
                return $site;
            }
        } catch (\Exception $e) {
            \Log::error('Error al obtener site en helper', [
                'message' => $e->getMessage(),
                'domain' => request()->getHost()
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
