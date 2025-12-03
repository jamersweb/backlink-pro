<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;

class SystemHealthController extends Controller
{
    public function index()
    {
        // Database connection status
        $dbStatus = 'connected';
        $dbLatency = null;
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $dbLatency = round((microtime(true) - $start) * 1000, 2);
        } catch (\Exception $e) {
            $dbStatus = 'disconnected';
        }

        // Redis connection status
        $redisStatus = 'connected';
        $redisLatency = null;
        try {
            $start = microtime(true);
            Redis::ping();
            $redisLatency = round((microtime(true) - $start) * 1000, 2);
        } catch (\Exception $e) {
            $redisStatus = 'disconnected';
        }

        // Queue sizes
        $queueSizes = [];
        try {
            $queues = ['default', 'high', 'low'];
            foreach ($queues as $queue) {
                $queueSizes[$queue] = Queue::size($queue);
            }
        } catch (\Exception $e) {
            $queueSizes = ['error' => 'Unable to fetch queue sizes'];
        }

        // Failed jobs count
        $failedJobsCount = DB::table('failed_jobs')->count();
        $recentFailedJobs = DB::table('failed_jobs')
            ->orderBy('failed_at', 'desc')
            ->limit(10)
            ->get();

        // System stats
        $stats = [
            'db_status' => $dbStatus,
            'db_latency' => $dbLatency,
            'redis_status' => $redisStatus,
            'redis_latency' => $redisLatency,
            'queue_sizes' => $queueSizes,
            'failed_jobs_count' => $failedJobsCount,
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
            'memory_peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB',
        ];

        // Automation tasks stats
        $automationStats = [
            'pending' => \App\Models\AutomationTask::where('status', 'pending')->count(),
            'running' => \App\Models\AutomationTask::where('status', 'running')->count(),
            'success' => \App\Models\AutomationTask::where('status', 'success')->count(),
            'failed' => \App\Models\AutomationTask::where('status', 'failed')->count(),
        ];

        return Inertia::render('Admin/SystemHealth/Index', [
            'stats' => $stats,
            'automationStats' => $automationStats,
            'recentFailedJobs' => $recentFailedJobs,
        ]);
    }

    public function retryFailedJob($id)
    {
        try {
            Artisan::call('queue:retry', ['id' => $id]);
            return back()->with('success', 'Failed job retried successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to retry job: ' . $e->getMessage());
        }
    }

    public function flushFailedJobs()
    {
        try {
            Artisan::call('queue:flush');
            return back()->with('success', 'All failed jobs flushed successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to flush jobs: ' . $e->getMessage());
        }
    }
}

