<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

class HealthController extends Controller
{
    /**
     * Health check endpoint for monitoring
     * 
     * Returns system status including database, Redis, and queue connections
     * Useful for load balancers, monitoring tools, and deployment checks
     */
    public function check(): JsonResponse
    {
        $status = [
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'version' => config('app.name') . ' v1.0',
            'environment' => config('app.env'),
        ];
        
        $hasErrors = false;
        
        // Check database connection
        try {
            DB::connection()->getPdo();
            $status['database'] = [
                'status' => 'connected',
                'connection' => config('database.default'),
            ];
        } catch (\Exception $e) {
            $status['database'] = [
                'status' => 'disconnected',
                'error' => $e->getMessage(),
            ];
            $hasErrors = true;
        }
        
        // Check Redis connection (if configured)
        $queueConnection = config('queue.default');
        $cacheStore = config('cache.default');
        
        if ($queueConnection === 'redis' || $cacheStore === 'redis') {
            try {
                Redis::ping();
                $status['redis'] = [
                    'status' => 'connected',
                    'host' => config('database.redis.default.host'),
                    'port' => config('database.redis.default.port'),
                ];
            } catch (\Exception $e) {
                $status['redis'] = [
                    'status' => 'disconnected',
                    'error' => $e->getMessage(),
                ];
                $hasErrors = true;
            }
        }
        
        // Check queue connection
        $status['queue'] = [
            'connection' => $queueConnection,
            'status' => 'configured',
        ];
        
        // Check cache
        try {
            Cache::put('health_check', 'ok', 10);
            $status['cache'] = [
                'status' => 'working',
                'store' => $cacheStore,
            ];
        } catch (\Exception $e) {
            $status['cache'] = [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
        
        // Determine HTTP status code
        $httpStatus = $hasErrors ? 503 : 200;
        
        return response()->json($status, $httpStatus);
    }
}

