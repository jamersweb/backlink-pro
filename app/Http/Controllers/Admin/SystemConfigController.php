<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Inertia\Inertia;

class SystemConfigController extends Controller
{
    /**
     * Display system configuration dashboard
     */
    public function index()
    {
        return Inertia::render('Admin/SystemConfig/Index', [
            'system' => $this->getSystemInfo(),
            'services' => $this->getServiceStatus(),
            'cache' => $this->getCacheInfo(),
            'queue' => $this->getQueueInfo(),
            'logs' => $this->getRecentLogs(),
        ]);
    }

    /**
     * Get system information
     */
    protected function getSystemInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'environment' => app()->environment(),
            'debug_mode' => config('app.debug'),
            'timezone' => config('app.timezone'),
            'locale' => config('app.locale'),
            'server' => [
                'os' => PHP_OS,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size'),
            ],
            'storage' => [
                'disk_free' => $this->formatBytes(disk_free_space(storage_path())),
                'disk_total' => $this->formatBytes(disk_total_space(storage_path())),
                'disk_usage_percent' => round((1 - (disk_free_space(storage_path()) / disk_total_space(storage_path()))) * 100, 1),
            ],
        ];
    }

    /**
     * Get service status
     */
    protected function getServiceStatus(): array
    {
        $services = [];

        // Database
        try {
            DB::connection()->getPdo();
            $services['database'] = [
                'status' => 'healthy',
                'driver' => config('database.default'),
                'message' => 'Connected',
            ];
        } catch (\Exception $e) {
            $services['database'] = [
                'status' => 'error',
                'driver' => config('database.default'),
                'message' => $e->getMessage(),
            ];
        }

        // Redis
        try {
            Redis::ping();
            $services['redis'] = [
                'status' => 'healthy',
                'host' => config('database.redis.default.host'),
                'message' => 'Connected',
            ];
        } catch (\Exception $e) {
            $services['redis'] = [
                'status' => 'error',
                'host' => config('database.redis.default.host'),
                'message' => $e->getMessage(),
            ];
        }

        // Cache
        try {
            Cache::store()->put('health-check', true, 1);
            Cache::store()->forget('health-check');
            $services['cache'] = [
                'status' => 'healthy',
                'driver' => config('cache.default'),
                'message' => 'Operational',
            ];
        } catch (\Exception $e) {
            $services['cache'] = [
                'status' => 'error',
                'driver' => config('cache.default'),
                'message' => $e->getMessage(),
            ];
        }

        // Queue
        try {
            $queueConnection = config('queue.default');
            $services['queue'] = [
                'status' => 'healthy',
                'driver' => $queueConnection,
                'message' => 'Configured',
            ];
        } catch (\Exception $e) {
            $services['queue'] = [
                'status' => 'error',
                'driver' => config('queue.default'),
                'message' => $e->getMessage(),
            ];
        }

        // Mail
        $services['mail'] = [
            'status' => config('mail.mailer') !== 'log' ? 'healthy' : 'warning',
            'driver' => config('mail.mailer'),
            'message' => config('mail.mailer') === 'log' ? 'Using log driver (not sending real emails)' : 'Configured',
        ];

        return $services;
    }

    /**
     * Get cache information
     */
    protected function getCacheInfo(): array
    {
        try {
            $info = [];
            
            if (config('cache.default') === 'redis') {
                $redisInfo = Redis::info();
                $info = [
                    'used_memory' => $redisInfo['used_memory_human'] ?? 'N/A',
                    'connected_clients' => $redisInfo['connected_clients'] ?? 'N/A',
                    'uptime_days' => round(($redisInfo['uptime_in_seconds'] ?? 0) / 86400, 1),
                    'keys' => Redis::dbsize(),
                ];
            }

            return $info;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get queue information
     */
    protected function getQueueInfo(): array
    {
        try {
            $pending = DB::table('jobs')->count();
            $failed = DB::table('failed_jobs')->count();
            $batches = DB::table('job_batches')->count();

            return [
                'pending_jobs' => $pending,
                'failed_jobs' => $failed,
                'batches' => $batches,
                'connection' => config('queue.default'),
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get recent log entries
     */
    protected function getRecentLogs(): array
    {
        try {
            $logFile = storage_path('logs/laravel.log');
            
            if (!file_exists($logFile)) {
                return [];
            }

            $logs = [];
            $lines = array_slice(file($logFile), -50); // Last 50 lines

            foreach (array_reverse($lines) as $line) {
                if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.(\w+): (.*)$/', $line, $matches)) {
                    $logs[] = [
                        'timestamp' => $matches[1],
                        'environment' => $matches[2],
                        'level' => $matches[3],
                        'message' => substr($matches[4], 0, 200),
                    ];
                }

                if (count($logs) >= 20) break;
            }

            return $logs;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Clear various caches
     */
    public function clearCache(Request $request)
    {
        $type = $request->input('type', 'all');

        try {
            switch ($type) {
                case 'application':
                    Artisan::call('cache:clear');
                    break;
                case 'config':
                    Artisan::call('config:clear');
                    break;
                case 'route':
                    Artisan::call('route:clear');
                    break;
                case 'view':
                    Artisan::call('view:clear');
                    break;
                case 'all':
                default:
                    Artisan::call('cache:clear');
                    Artisan::call('config:clear');
                    Artisan::call('route:clear');
                    Artisan::call('view:clear');
                    break;
            }

            Log::info("Cache cleared: {$type}", ['user' => auth()->id()]);

            return back()->with('success', ucfirst($type) . ' cache cleared successfully.');
        } catch (\Exception $e) {
            Log::error('Cache clear failed', ['type' => $type, 'error' => $e->getMessage()]);
            return back()->with('error', 'Failed to clear cache: ' . $e->getMessage());
        }
    }

    /**
     * Optimize application
     */
    public function optimize()
    {
        try {
            Artisan::call('optimize');
            Artisan::call('view:cache');
            
            Log::info('Application optimized', ['user' => auth()->id()]);

            return back()->with('success', 'Application optimized successfully.');
        } catch (\Exception $e) {
            Log::error('Optimization failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to optimize: ' . $e->getMessage());
        }
    }

    /**
     * Run database migrations
     */
    public function migrate()
    {
        if (!app()->environment('local', 'staging')) {
            return back()->with('error', 'Migrations can only be run in local or staging environment.');
        }

        try {
            Artisan::call('migrate', ['--force' => true]);
            
            Log::info('Migrations run', ['user' => auth()->id()]);

            return back()->with('success', 'Migrations completed successfully.');
        } catch (\Exception $e) {
            Log::error('Migration failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Migration failed: ' . $e->getMessage());
        }
    }

    /**
     * Retry failed jobs
     */
    public function retryFailedJobs()
    {
        try {
            Artisan::call('queue:retry', ['id' => 'all']);
            
            Log::info('Failed jobs retried', ['user' => auth()->id()]);

            return back()->with('success', 'All failed jobs have been pushed back to the queue.');
        } catch (\Exception $e) {
            Log::error('Retry failed jobs error', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to retry jobs: ' . $e->getMessage());
        }
    }

    /**
     * Flush failed jobs
     */
    public function flushFailedJobs()
    {
        try {
            Artisan::call('queue:flush');
            
            Log::info('Failed jobs flushed', ['user' => auth()->id()]);

            return back()->with('success', 'All failed jobs have been deleted.');
        } catch (\Exception $e) {
            Log::error('Flush failed jobs error', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to flush jobs: ' . $e->getMessage());
        }
    }

    /**
     * Format bytes to human readable
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
