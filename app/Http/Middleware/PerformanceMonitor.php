<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Middleware de monitorización de rendimiento.
 *
 * Activar en .env: PERF_MONITOR=true
 * Logs en storage/logs/laravel.log con prefijo [PERF]
 */
class PerformanceMonitor
{
    public function handle(Request $request, Closure $next)
    {
        if (!config('app.perf_monitor')) {
            return $next($request);
        }

        $startTime   = microtime(true);
        $startMemory = memory_get_usage(true);

        DB::enableQueryLog();

        $response = $next($request);

        $duration   = round((microtime(true) - $startTime) * 1000, 2);
        $memory     = round((memory_get_usage(true) - $startMemory) / 1024 / 1024, 2);
        $peakMem    = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
        $queries    = DB::getQueryLog();
        $queryTime  = round(array_sum(array_column($queries, 'time')), 2);
        $queryCount = count($queries);
        $slowQueries = array_filter($queries, fn($q) => $q['time'] > 100);

        $context = [
            'url'       => $request->method() . ' ' . $request->path(),
            'total_ms'  => $duration,
            'query_ms'  => $queryTime,
            'php_ms'    => round($duration - $queryTime, 2),
            'queries'   => $queryCount,
            'memory_mb' => $memory,
            'peak_mb'   => $peakMem,
            'status'    => $response->getStatusCode(),
        ];

        if ($duration > 1000) {
            Log::warning('[PERF] Request LENTO', $context);
        } elseif ($duration > 500) {
            Log::notice('[PERF] Request moderado', $context);
        } else {
            Log::info('[PERF] Request OK', $context);
        }

        foreach ($slowQueries as $q) {
            Log::warning('[PERF] Query lenta: ' . round($q['time'], 2) . 'ms', [
                'sql'      => $q['query'],
                'bindings' => $q['bindings'],
            ]);
        }

        if (!app()->isProduction()) {
            $response->headers->set('X-Perf-Ms', $duration);
            $response->headers->set('X-Perf-Queries', $queryCount);
            $response->headers->set('X-Perf-Mem-MB', $peakMem);
        }

        return $response;
    }
}
