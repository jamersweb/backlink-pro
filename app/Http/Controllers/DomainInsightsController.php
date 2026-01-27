<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\DomainInsightRun;
use App\Models\DomainTask;
use App\Models\DomainAlert;
use App\Models\DomainKpiSnapshot;
use App\Models\AutomationCampaign;
use App\Models\AutomationTarget;
use App\Models\DomainBacklinkRun;
use App\Jobs\Insights\GenerateDomainInsightsJob;
use App\Services\Automation\DecisionEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class DomainInsightsController extends Controller
{
    /**
     * Show insights dashboard
     */
    public function index(Domain $domain)
    {
        // Authorize domain ownership
        if ($domain->user_id !== Auth::id()) {
            abort(403);
        }

        // Get latest insight run
        $latestRun = DomainInsightRun::where('domain_id', $domain->id)
            ->where('status', DomainInsightRun::STATUS_COMPLETED)
            ->latest()
            ->first();

        // Get open tasks (top 10 by priority & impact)
        $openTasks = DomainTask::where('domain_id', $domain->id)
            ->where('status', DomainTask::STATUS_OPEN)
            ->orderByRaw("FIELD(priority, 'p1', 'p2', 'p3')")
            ->orderBy('impact_score', 'desc')
            ->limit(10)
            ->get();

        // Get doing tasks
        $doingTasks = DomainTask::where('domain_id', $domain->id)
            ->where('status', DomainTask::STATUS_DOING)
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        // Get done tasks (recent)
        $doneTasks = DomainTask::where('domain_id', $domain->id)
            ->where('status', DomainTask::STATUS_DONE)
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        // Get latest alerts (unread first)
        $alerts = DomainAlert::where('domain_id', $domain->id)
            ->orderBy('is_read')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        // Get KPI chart data (last 30 days)
        $kpiData = DomainKpiSnapshot::where('domain_id', $domain->id)
            ->where('date', '>=', Carbon::now()->subDays(30))
            ->orderBy('date')
            ->get();

        $summary = $latestRun?->summary_json ?? [];

        return Inertia::render('Domains/Insights/Index', [
            'domain' => $domain,
            'latestRun' => $latestRun,
            'summary' => $summary,
            'openTasks' => $openTasks,
            'doingTasks' => $doingTasks,
            'doneTasks' => $doneTasks,
            'alerts' => $alerts,
            'kpiData' => $kpiData,
        ]);
    }

    /**
     * Run insights generation now
     */
    public function runNow(Domain $domain)
    {
        // Authorize domain ownership
        if ($domain->user_id !== Auth::id()) {
            abort(403);
        }

        // Check quota limits
        $user = Auth::user();
        try {
            $quotaService = app(\App\Services\Usage\QuotaService::class);
            $quotaService->assertCan($user, 'insights.runs_per_day', 1);
        } catch (\App\Exceptions\QuotaExceededException $e) {
            return back()->withErrors([
                'quota' => $e->getMessage()
            ]);
        }

        // Consume quota
        $quotaService = app(\App\Services\Usage\QuotaService::class);
        $quotaService->consume($user, 'insights.runs_per_day', 1, 'day', [
            'domain_id' => $domain->id,
        ]);

        // Dispatch job
        GenerateDomainInsightsJob::dispatch($domain->id);

        return back()->with('success', 'Insights generation started. This may take a few minutes.');
    }

    /**
     * Mark alert as read
     */
    public function markAlertRead(Domain $domain, DomainAlert $alert)
    {
        // Authorize domain ownership
        if ($domain->user_id !== Auth::id() || $alert->domain_id !== $domain->id) {
            abort(403);
        }

        $alert->update(['is_read' => true]);

        return back()->with('success', 'Alert marked as read');
    }

    /**
     * Create automation campaign from lost links
     */
    public function createCampaignFromLostLinks(Domain $domain)
    {
        Gate::authorize('domain.view', $domain);

        $latestRun = DomainBacklinkRun::where('domain_id', $domain->id)
            ->where('status', DomainBacklinkRun::STATUS_COMPLETED)
            ->latest()
            ->first();

        if (!$latestRun || !$latestRun->delta || ($latestRun->delta->lost_links ?? 0) === 0) {
            return back()->with('error', 'No lost links found in latest backlink run');
        }

        // Get lost links (from previous run that are not in current)
        $currentFingerprints = $latestRun->backlinks()->pluck('fingerprint')->toArray();
        $previousRun = DomainBacklinkRun::where('domain_id', $domain->id)
            ->where('status', DomainBacklinkRun::STATUS_COMPLETED)
            ->where('id', '<', $latestRun->id)
            ->latest()
            ->first();

        if (!$previousRun) {
            return back()->with('error', 'No previous run found for comparison');
        }

        $lostBacklinks = $previousRun->backlinks()
            ->whereNotIn('fingerprint', $currentFingerprints)
            ->limit(500)
            ->get();

        if ($lostBacklinks->isEmpty()) {
            return back()->with('error', 'No lost links found');
        }

        // Create campaign
        $campaign = AutomationCampaign::create([
            'user_id' => Auth::id(),
            'domain_id' => $domain->id,
            'name' => "Recover Lost Links - " . now()->format('Y-m-d'),
            'status' => AutomationCampaign::STATUS_DRAFT,
            'rules_json' => [
                'allowed_actions' => ['comment', 'profile', 'forum', 'guest'],
                'mode' => 'deterministic',
                'max_retries' => 2,
                'headless' => true,
                'use_proxy' => false,
            ],
        ]);

        // Import lost links as targets
        $decisionEngine = new DecisionEngine();
        $allowedActions = $campaign->rules_json['allowed_actions'];
        $imported = 0;

        DB::transaction(function() use ($campaign, $lostBacklinks, $decisionEngine, $allowedActions, &$imported) {
            foreach ($lostBacklinks as $backlink) {
                AutomationTarget::create([
                    'campaign_id' => $campaign->id,
                    'url' => $backlink->source_url,
                    'url_hash' => hash('sha256', $backlink->source_url),
                    'source' => AutomationTarget::SOURCE_INSIGHTS,
                    'anchor_text' => $backlink->anchor,
                    'target_link' => $backlink->target_url,
                    'created_at' => now(),
                ]);
                $imported++;
            }
        });

        $activityLogger = app(\App\Services\System\ActivityLogger::class);
        $activityLogger->info('automation', 'campaign_created_from_insights', "Campaign created from lost links: {$campaign->name}", Auth::id(), $domain->id, [
            'campaign_id' => $campaign->id,
            'targets_imported' => $imported,
        ]);

        return redirect()->route('domains.automation.show', [$domain->id, $campaign->id])
            ->with('success', "Campaign created with {$imported} lost links. Review and start when ready.");
    }
}
