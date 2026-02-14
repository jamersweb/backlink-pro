<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\ConnectedAccount;
use App\Jobs\RunSeoAuditJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AuditReportController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Get connected accounts for Google integrations
        $googleSeoAccount = ConnectedAccount::where('user_id', $user->id)
            ->where('provider', 'google')
            ->where('service', 'seo')
            ->where('status', 'active')
            ->first();
        
        // Get recent audits
        $recentAudits = Audit::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['id', 'url', 'status', 'overall_score', 'created_at', 'finished_at']);
        
        return Inertia::render('AuditReport', [
            'googleConnected' => (bool) $googleSeoAccount,
            'googleEmail' => $googleSeoAccount?->email,
            'recentAudits' => $recentAudits,
        ]);
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'url' => 'required|url|max:2048',
            'email' => 'nullable|email',
            'send_to_email' => 'boolean',
        ]);

        $user = Auth::user();
        
        // Normalize URL
        $normalizedUrl = $this->normalizeUrl($validated['url']);
        
        // Create audit record
        $audit = Audit::create([
            'user_id' => $user->id,
            'url' => $validated['url'],
            'normalized_url' => $normalizedUrl,
            'status' => Audit::STATUS_QUEUED,
            'mode' => Audit::MODE_AUTH,
            'lead_email' => $validated['send_to_email'] ? $validated['email'] : null,
            'share_token' => Str::random(32),
            'pages_limit' => 1, // Single page audit for MVP (fast)
            'crawl_depth' => 0, // No crawling, just homepage
            'started_at' => now(),
        ]);
        
        // Log audit creation
        \Log::info('User audit created', [
            'audit_id' => $audit->id,
            'url' => $audit->url,
            'normalized_url' => $normalizedUrl,
            'user_id' => $user->id,
        ]);
        
        // MVP: Run audit with sync queue (no separate worker process needed)
        // 
        // SETUP NOTES:
        // - Queue is set to 'sync' in .env (QUEUE_CONNECTION=sync)
        // - This runs audits immediately in the HTTP request (takes 30-60s)
        // - No need to run: php artisan queue:work
        // 
        // PRODUCTION ALTERNATIVE:
        // - Change QUEUE_CONNECTION=database or redis
        // - Run: php artisan queue:work --queue=audits
        // - Audits process in background, HTTP request returns immediately
        try {
            $queueDriver = config('queue.default');
            
            \Log::info('Starting user audit', [
                'audit_id' => $audit->id,
                'queue_driver' => $queueDriver,
            ]);
            
            if ($queueDriver === 'sync' || app()->environment('local')) {
                // Sync: Run immediately (takes 30-60s, but no worker needed)
                RunSeoAuditJob::dispatchSync($audit->id);
                $audit->refresh();
                
                \Log::info('User audit completed immediately', [
                    'audit_id' => $audit->id,
                    'status' => $audit->status,
                    'score' => $audit->overall_score,
                ]);
            } else {
                // Async: Queue for worker processing
                \Log::info('Queuing user audit for worker', ['audit_id' => $audit->id]);
                RunSeoAuditJob::dispatch($audit->id)->onQueue('audits');
            }
        } catch (\Exception $e) {
            \Log::error('User audit dispatch failed', [
                'audit_id' => $audit->id,
                'error' => $e->getMessage(),
            ]);
            
            $audit->status = Audit::STATUS_FAILED;
            $audit->error = 'Failed to start audit: ' . $e->getMessage();
            $audit->finished_at = now();
            $audit->save();
        }
        
        return response()->json([
            'success' => true,
            'audit_id' => $audit->id,
            'status' => $audit->status,
            'message' => 'Audit processed successfully',
        ]);
    }

    public function show(Request $request, $id)
    {
        $audit = Audit::with(['pages', 'issues'])
            ->where('user_id', Auth::id())
            ->findOrFail($id);
        
        // If AJAX request, return JSON
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'audit' => $audit,
            ]);
        }
        
        // Otherwise return Inertia view
        return Inertia::render('AuditReportView', [
            'audit' => $audit,
        ]);
    }

    public function status($id)
    {
        $audit = Audit::where('user_id', Auth::id())
            ->findOrFail($id);
        
        return response()->json([
            'id' => $audit->id,
            'status' => $audit->status,
            'progress_percent' => $audit->progress_percent ?? 0,
            'overall_score' => $audit->overall_score,
            'started_at' => $audit->started_at,
            'finished_at' => $audit->finished_at,
            'error' => $audit->error,
        ]);
    }

    private function normalizeUrl($url)
    {
        $url = trim($url);
        
        // Add https if no scheme
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }
        
        // Remove trailing slash
        $url = rtrim($url, '/');
        
        return $url;
    }
}
