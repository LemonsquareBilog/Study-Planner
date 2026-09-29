<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    /**
     * Basic health check - returns 200 if application is running.
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'version' => config('app.version', '1.0.0'),
            'environment' => app()->environment(),
        ]);
    }

    /**
     * Readiness check - verifies all dependencies are available.
     * Used by Kubernetes/liveness probes.
     */
    public function ready(): JsonResponse
    {
        $checks = [];
        $allHealthy = true;

        // Database check
        try {
            DB::connection()->getPdo();
            $checks['database'] = ['status' => 'healthy', 'latency_ms' => $this->measureLatency(fn() => DB::select('SELECT 1'))];
        } catch (\Throwable $e) {
            $checks['database'] = ['status' => 'unhealthy', 'error' => $e->getMessage()];
            $allHealthy = false;
        }

        // Cache check
        try {
            $key = 'health_check_' . uniqid();
            Cache::put($key, 'ok', 10);
            $result = Cache::get($key);
            Cache::forget($key);
            $checks['cache'] = ['status' => $result === 'ok' ? 'healthy' : 'unhealthy'];
            if ($result !== 'ok') $allHealthy = false;
        } catch (\Throwable $e) {
            $checks['cache'] = ['status' => 'unhealthy', 'error' => $e->getMessage()];
            $allHealthy = false;
        }

        // Redis check (if configured)
        if (config('database.redis.client')) {
            try {
                Redis::ping();
                $checks['redis'] = ['status' => 'healthy'];
            } catch (\Throwable $e) {
                $checks['redis'] = ['status' => 'unhealthy', 'error' => $e->getMessage()];
                $allHealthy = false;
            }
        }

        // Queue check
        try {
            $queueSize = app('queue')->size();
            $checks['queue'] = ['status' => 'healthy', 'pending_jobs' => $queueSize];
        } catch (\Throwable $e) {
            $checks['queue'] = ['status' => 'degraded', 'error' => $e->getMessage()];
        }

        $statusCode = $allHealthy ? 200 : 503;

        return response()->json([
            'status' => $allHealthy ? 'ready' : 'not_ready',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ], $statusCode);
    }

    /**
     * Liveness check - minimal check for process health.
     * Used by Kubernetes to detect if process should be restarted.
     */
    public function live(): JsonResponse
    {
        return response()->json([
            'status' => 'alive',
            'timestamp' => now()->toIso8601String(),
            'pid' => getmypid(),
            'memory_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
        ]);
    }

    private function measureLatency(callable $callback): float
    {
        $start = microtime(true);
        $callback();
        return round((microtime(true) - $start) * 1000, 2);
    }
}