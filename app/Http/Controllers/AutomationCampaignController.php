<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\AutomationCampaign;
use App\Models\AutomationTarget;
use App\Models\AutomationJob;
use App\Models\DomainBacklinkRun;
use App\Services\Automation\DecisionEngine;
use App\Services\Usage\QuotaService;
use App\Services\System\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AutomationCampaignController extends Controller
{
    protected DecisionEngine $decisionEngine;

    public function __construct()
    {
        $this->decisionEngine = new DecisionEngine();
    }

    /**
     * List campaigns
     */
    public function index(Domain $domain)
    {
        Gate::authorize('domain.view', $domain);

        $campaigns = AutomationCampaign::where('domain_id', $domain->id)
            ->where('user_id', Auth::id())
            ->withCount(['targets', 'jobs'])
            ->latest()
            ->paginate(20);

        return Inertia::render('Domains/Automation/Index', [
            'domain' => $domain,
            'campaigns' => $campaigns,
        ]);
    }

    /**
     * Show create form
     */
    public function create(Domain $domain)
    {
        Gate::authorize('domain.view', $domain);

        return Inertia::render('Domains/Automation/Create', [
            'domain' => $domain,
        ]);
    }

    /**
     * Store campaign
     */
    public function store(Request $request, Domain $domain)
    {
        Gate::authorize('domain.view', $domain);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'allowed_actions' => 'required|array|min:1',
            'allowed_actions.*' => 'in:comment,profile,forum,guest',
            'max_retries' => 'nullable|integer|min:0|max:5',
            'headless' => 'nullable|boolean',
            'use_proxy' => 'nullable|boolean',
        ]);

        // Check campaign quota
        $quotaService = app(QuotaService::class);
        try {
            $quotaService->assertCan(Auth::user(), 'automation.campaigns_per_month', 1);
        } catch (\App\Exceptions\QuotaExceededException $e) {
            return back()->with('error', $e->getMessage());
        }

        $campaign = AutomationCampaign::create([
            'user_id' => Auth::id(),
            'domain_id' => $domain->id,
            'name' => $validated['name'],
            'status' => AutomationCampaign::STATUS_DRAFT,
            'rules_json' => [
                'allowed_actions' => $validated['allowed_actions'],
                'mode' => 'deterministic',
                'max_retries' => $validated['max_retries'] ?? 2,
                'headless' => $validated['headless'] ?? true,
                'use_proxy' => $validated['use_proxy'] ?? false,
            ],
        ]);

        // Record campaign creation usage
        $quotaService->consume(Auth::user(), 'automation.campaigns', 1, 'month', [
            'domain_id' => $domain->id,
            'campaign_id' => $campaign->id,
        ]);

        $activityLogger = app(ActivityLogger::class);
        $activityLogger->info('automation', 'campaign_created', "Campaign created: {$campaign->name}", Auth::id(), $domain->id, [
            'campaign_id' => $campaign->id,
        ]);

        return redirect()->route('domains.automation.show', [$domain->id, $campaign->id])
            ->with('success', 'Campaign created. Add targets to get started.');
    }

    /**
     * Show campaign
     */
    public function show(Domain $domain, AutomationCampaign $campaign)
    {
        Gate::authorize('domain.view', $domain);

        if ($campaign->domain_id !== $domain->id || $campaign->user_id !== Auth::id()) {
            abort(403);
        }

        $campaign->load(['targets', 'jobs' => function($q) {
            $q->latest()->limit(100);
        }]);

        $stats = [
            'total_targets' => $campaign->targets()->count(),
            'total_jobs' => $campaign->jobs()->count(),
            'success' => $campaign->jobs()->where('status', AutomationJob::STATUS_SUCCESS)->count(),
            'failed' => $campaign->jobs()->where('status', AutomationJob::STATUS_FAILED)->count(),
            'pending' => $campaign->jobs()->whereIn('status', [AutomationJob::STATUS_QUEUED, AutomationJob::STATUS_LOCKED, AutomationJob::STATUS_RUNNING, AutomationJob::STATUS_RETRYING])->count(),
        ];

        return Inertia::render('Domains/Automation/Show', [
            'domain' => $domain,
            'campaign' => $campaign,
            'stats' => $stats,
        ]);
    }

    /**
     * Start campaign
     */
    public function start(Domain $domain, AutomationCampaign $campaign)
    {
        Gate::authorize('domain.view', $domain);

        if ($campaign->domain_id !== $domain->id || $campaign->user_id !== Auth::id()) {
            abort(403);
        }

        if ($campaign->status !== AutomationCampaign::STATUS_DRAFT) {
            return back()->with('error', 'Campaign can only be started from draft status');
        }

        $targets = $campaign->targets;
        if ($targets->isEmpty()) {
            return back()->with('error', 'Add targets before starting the campaign');
        }

        // Check quota
        $quotaService = app(QuotaService::class);
        try {
            $quotaService->assertCan(Auth::user(), 'automation.jobs_per_month', $targets->count());
        } catch (\App\Exceptions\QuotaExceededException $e) {
            return back()->with('error', $e->getMessage());
        }

        // Create jobs from targets
        $allowedActions = $campaign->rules_json['allowed_actions'] ?? ['comment', 'profile', 'forum', 'guest'];
        $jobsCreated = 0;

        DB::transaction(function() use ($campaign, $targets, $allowedActions, &$jobsCreated) {
            foreach ($targets as $target) {
                $action = $this->decisionEngine->decideAction($target->url, $allowedActions);
                if (!$action) {
                    continue;
                }

                AutomationJob::create([
                    'campaign_id' => $campaign->id,
                    'target_id' => $target->id,
                    'user_id' => $campaign->user_id,
                    'domain_id' => $campaign->domain_id,
                    'action' => $action,
                    'status' => AutomationJob::STATUS_QUEUED,
                    'priority' => 5,
                    'max_attempts' => $campaign->rules_json['max_retries'] ?? 2,
                ]);
                $jobsCreated++;
            }

            $campaign->update([
                'status' => AutomationCampaign::STATUS_QUEUED,
                'started_at' => now(),
            ]);
        });

        // Record usage
        $quotaService->consume(Auth::user(), 'automation.jobs', $jobsCreated, 'month', [
            'domain_id' => $domain->id,
            'campaign_id' => $campaign->id,
        ]);

        $activityLogger = app(ActivityLogger::class);
        $activityLogger->info('automation', 'campaign_started', "Campaign started: {$campaign->name}", Auth::id(), $domain->id, [
            'campaign_id' => $campaign->id,
            'jobs_created' => $jobsCreated,
        ]);

        return back()->with('success', "Campaign started. {$jobsCreated} jobs created.");
    }

    /**
     * Pause campaign
     */
    public function pause(Domain $domain, AutomationCampaign $campaign)
    {
        Gate::authorize('domain.view', $domain);

        if ($campaign->domain_id !== $domain->id || $campaign->user_id !== Auth::id()) {
            abort(403);
        }

        if (!in_array($campaign->status, [AutomationCampaign::STATUS_QUEUED, AutomationCampaign::STATUS_RUNNING])) {
            return back()->with('error', 'Campaign can only be paused when running');
        }

        $campaign->update(['status' => AutomationCampaign::STATUS_PAUSED]);

        $activityLogger = app(ActivityLogger::class);
        $activityLogger->info('automation', 'campaign_paused', "Campaign paused: {$campaign->name}", Auth::id(), $domain->id, [
            'campaign_id' => $campaign->id,
        ]);

        return back()->with('success', 'Campaign paused');
    }

    /**
     * Resume campaign
     */
    public function resume(Domain $domain, AutomationCampaign $campaign)
    {
        Gate::authorize('domain.view', $domain);

        if ($campaign->domain_id !== $domain->id || $campaign->user_id !== Auth::id()) {
            abort(403);
        }

        if ($campaign->status !== AutomationCampaign::STATUS_PAUSED) {
            return back()->with('error', 'Campaign can only be resumed when paused');
        }

        $campaign->update(['status' => AutomationCampaign::STATUS_QUEUED]);

        $activityLogger = app(ActivityLogger::class);
        $activityLogger->info('automation', 'campaign_resumed', "Campaign resumed: {$campaign->name}", Auth::id(), $domain->id, [
            'campaign_id' => $campaign->id,
        ]);

        return back()->with('success', 'Campaign resumed');
    }

    /**
     * Stop campaign
     */
    public function stop(Domain $domain, AutomationCampaign $campaign)
    {
        Gate::authorize('domain.view', $domain);

        if ($campaign->domain_id !== $domain->id || $campaign->user_id !== Auth::id()) {
            abort(403);
        }

        $campaign->update([
            'status' => AutomationCampaign::STATUS_COMPLETED,
            'finished_at' => now(),
        ]);

        $activityLogger = app(ActivityLogger::class);
        $activityLogger->info('automation', 'campaign_stopped', "Campaign stopped: {$campaign->name}", Auth::id(), $domain->id, [
            'campaign_id' => $campaign->id,
        ]);

        return back()->with('success', 'Campaign stopped');
    }

    /**
     * Import targets from CSV
     */
    public function importCsv(Request $request, Domain $domain, AutomationCampaign $campaign)
    {
        Gate::authorize('domain.view', $domain);

        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $file = $request->file('file');
        $lines = file($file->getRealPath(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $imported = 0;

        DB::transaction(function() use ($campaign, $lines, &$imported) {
            foreach ($lines as $line) {
                $data = str_getcsv($line);
                if (empty($data[0])) {
                    continue;
                }

                $url = trim($data[0]);
                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                    continue;
                }

                AutomationTarget::firstOrCreate(
                    [
                        'campaign_id' => $campaign->id,
                        'url_hash' => hash('sha256', $url),
                    ],
                    [
                        'url' => $url,
                        'source' => AutomationTarget::SOURCE_CSV,
                        'anchor_text' => $data[1] ?? null,
                        'target_link' => $data[2] ?? null,
                        'keyword' => $data[3] ?? null,
                        'created_at' => now(),
                    ]
                );
                $imported++;
            }
        });

        return back()->with('success', "{$imported} targets imported from CSV");
    }

    /**
     * Import targets from backlinks run
     */
    public function importBacklinksRun(Request $request, Domain $domain, AutomationCampaign $campaign)
    {
        Gate::authorize('domain.view', $domain);

        $request->validate([
            'run_id' => 'required|exists:domain_backlink_runs,id',
        ]);

        $run = DomainBacklinkRun::findOrFail($request->run_id);
        if ($run->domain_id !== $domain->id) {
            abort(403);
        }

        $backlinks = $run->backlinks()->limit(1000)->get();
        $imported = 0;

        DB::transaction(function() use ($campaign, $backlinks, &$imported) {
            foreach ($backlinks as $backlink) {
                AutomationTarget::firstOrCreate(
                    [
                        'campaign_id' => $campaign->id,
                        'url_hash' => hash('sha256', $backlink->source_url),
                    ],
                    [
                        'url' => $backlink->source_url,
                        'source' => AutomationTarget::SOURCE_BACKLINKS_RUN,
                        'anchor_text' => $backlink->anchor,
                        'created_at' => now(),
                    ]
                );
                $imported++;
            }
        });

        return back()->with('success', "{$imported} targets imported from backlinks run");
    }

    /**
     * Add manual target
     */
    public function addManual(Request $request, Domain $domain, AutomationCampaign $campaign)
    {
        Gate::authorize('domain.view', $domain);

        $validated = $request->validate([
            'url' => 'required|url',
            'anchor_text' => 'nullable|string',
            'target_link' => 'nullable|url',
            'keyword' => 'nullable|string',
        ]);

        AutomationTarget::create([
            'campaign_id' => $campaign->id,
            'url' => $validated['url'],
            'url_hash' => hash('sha256', $validated['url']),
            'source' => AutomationTarget::SOURCE_MANUAL,
            'anchor_text' => $validated['anchor_text'] ?? null,
            'target_link' => $validated['target_link'] ?? null,
            'keyword' => $validated['keyword'] ?? null,
            'created_at' => now(),
        ]);

        return back()->with('success', 'Target added');
    }

    /**
     * Retry job
     */
    public function retryJob(Domain $domain, AutomationJob $job)
    {
        Gate::authorize('domain.view', $domain);

        if ($job->domain_id !== $domain->id || $job->user_id !== Auth::id()) {
            abort(403);
        }

        if (!$job->canRetry()) {
            return back()->with('error', 'Job cannot be retried');
        }

        $job->update([
            'status' => AutomationJob::STATUS_QUEUED,
            'error_code' => null,
            'error_message' => null,
            'attempts' => $job->attempts + 1,
        ]);

        return back()->with('success', 'Job queued for retry');
    }

    /**
     * Skip job
     */
    public function skipJob(Domain $domain, AutomationJob $job)
    {
        Gate::authorize('domain.view', $domain);

        if ($job->domain_id !== $domain->id || $job->user_id !== Auth::id()) {
            abort(403);
        }

        $job->update(['status' => AutomationJob::STATUS_SKIPPED]);

        return back()->with('success', 'Job skipped');
    }
}
