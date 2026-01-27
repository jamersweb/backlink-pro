<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemActivityLog;
use App\Models\JobFailure;
use App\Models\DomainAudit;
use App\Models\DomainBacklinkRun;
use App\Models\DomainMetaChange;
use App\Models\DomainInsightRun;
use App\Models\DomainGoogleIntegration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;
use Carbon\Carbon;

class SystemHealthController extends Controller
{
    /**
     * Show system health dashboard
     */
    public function index()
    {
        $last24h = Carbon::now()->subDay();

        // Queue overview
        $failedJobsCount = DB::table('failed_jobs')
            ->where('failed_at', '>=', $last24h)
            ->count();

        $errorLogsCount = SystemActivityLog::where('status', SystemActivityLog::STATUS_ERROR)
            ->where('created_at', '>=', $last24h)
            ->count();

        // Recent failures
        $recentFailures = JobFailure::with(['domain', 'user'])
            ->orderBy('failed_at', 'desc')
            ->limit(20)
            ->get();

        // Feature run status counts (last 24h)
        $auditsRunning = DomainAudit::where('status', DomainAudit::STATUS_RUNNING)
            ->where('created_at', '>=', $last24h)
            ->count();
        $auditsFailed = DomainAudit::where('status', DomainAudit::STATUS_FAILED)
            ->where('created_at', '>=', $last24h)
            ->count();

        $backlinksRunning = DomainBacklinkRun::where('status', DomainBacklinkRun::STATUS_RUNNING)
            ->where('created_at', '>=', $last24h)
            ->count();
        $backlinksFailed = DomainBacklinkRun::where('status', DomainBacklinkRun::STATUS_FAILED)
            ->where('created_at', '>=', $last24h)
            ->count();

        $metaQueued = DomainMetaChange::where('status', DomainMetaChange::STATUS_QUEUED)
            ->where('created_at', '>=', $last24h)
            ->count();
        $metaFailed = DomainMetaChange::where('status', DomainMetaChange::STATUS_FAILED)
            ->where('created_at', '>=', $last24h)
            ->count();

        $googleSyncFailed = DomainGoogleIntegration::where('status', DomainGoogleIntegration::STATUS_ERROR)
            ->where('updated_at', '>=', $last24h)
            ->count();

        $insightsFailed = DomainInsightRun::where('status', DomainInsightRun::STATUS_FAILED)
            ->where('created_at', '>=', $last24h)
            ->count();

        // Top error messages
        $topErrors = JobFailure::select('exception_message', DB::raw('count(*) as count'))
            ->where('failed_at', '>=', $last24h)
            ->whereNotNull('exception_message')
            ->groupBy('exception_message')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        return Inertia::render('Admin/SystemHealth/Index', [
            'overview' => [
                'failed_jobs_24h' => $failedJobsCount,
                'error_logs_24h' => $errorLogsCount,
            ],
            'recentFailures' => $recentFailures,
            'runStatus' => [
                'audits' => ['running' => $auditsRunning, 'failed' => $auditsFailed],
                'backlinks' => ['running' => $backlinksRunning, 'failed' => $backlinksFailed],
                'meta' => ['queued' => $metaQueued, 'failed' => $metaFailed],
                'google' => ['failed' => $googleSyncFailed],
                'insights' => ['failed' => $insightsFailed],
            ],
            'topErrors' => $topErrors,
        ]);
    }

    /**
     * Show activity logs
     */
    public function activity(Request $request)
    {
        $query = SystemActivityLog::with(['user', 'domain'])
            ->orderBy('created_at', 'desc');

        // Filters
        if ($request->has('feature')) {
            $query->where('feature', $request->feature);
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(50)->withQueryString();

        return Inertia::render('Admin/SystemHealth/Activity', [
            'logs' => $logs,
            'filters' => $request->only(['feature', 'status', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Show job failures
     */
    public function failures(Request $request)
    {
        $query = JobFailure::with(['user', 'domain'])
            ->orderBy('failed_at', 'desc');

        if ($request->has('feature')) {
            $query->where('feature', $request->feature);
        }
        if ($request->has('date_from')) {
            $query->where('failed_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('failed_at', '<=', $request->date_to);
        }

        $failures = $query->paginate(50)->withQueryString();

        return Inertia::render('Admin/SystemHealth/Failures', [
            'failures' => $failures,
            'filters' => $request->only(['feature', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Retry a failed job from Laravel's failed_jobs table
     */
    public function retryFailedJob($id)
    {
        try {
            Artisan::call('queue:retry', ['id' => $id]);
            
            return back()->with('success', 'Failed job has been retried.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to retry job: ' . $e->getMessage()]);
        }
    }

    /**
     * Flush all failed jobs from Laravel's failed_jobs table
     */
    public function flushFailedJobs()
    {
        try {
            Artisan::call('queue:flush');
            
            return back()->with('success', 'All failed jobs have been flushed.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to flush jobs: ' . $e->getMessage()]);
        }
    }
}
