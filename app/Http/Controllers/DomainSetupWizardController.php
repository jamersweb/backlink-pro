<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\DomainOnboarding;
use App\Models\DomainAudit;
use App\Models\DomainBacklinkRun;
use App\Models\DomainInsightRun;
use App\Models\PublicReport;
use App\Services\System\ActivityLogger;
use App\Services\Usage\QuotaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class DomainSetupWizardController extends Controller
{
    /**
     * Show setup wizard
     */
    public function show(Domain $domain)
    {
        Gate::authorize('domain.view', $domain);

        // Get or create onboarding record
        $onboarding = DomainOnboarding::firstOrCreate(
            ['domain_id' => $domain->id],
            [
                'user_id' => Auth::id(),
                'status' => DomainOnboarding::STATUS_IN_PROGRESS,
                'steps_json' => [],
            ]
        );

        // Auto-sync steps from real data (source of truth)
        $steps = $this->syncSteps($domain, $onboarding);
        $onboarding->update(['steps_json' => $steps]);

        // Check quotas
        $quotaService = app(QuotaService::class);
        $quotaBlocked = $this->checkQuotas($domain, $quotaService);

        // Load related data for status display
        $latestAudit = DomainAudit::where('domain_id', $domain->id)
            ->latest()
            ->first();

        $latestBacklinkRun = DomainBacklinkRun::where('domain_id', $domain->id)
            ->latest()
            ->first();

        $latestInsightRun = DomainInsightRun::where('domain_id', $domain->id)
            ->latest()
            ->first();

        $googleIntegration = $domain->googleIntegration;
        $metaConnector = $domain->metaConnector;
        $latestReport = PublicReport::where('domain_id', $domain->id)
            ->latest()
            ->first();

        return Inertia::render('Domains/Setup/Show', [
            'domain' => $domain,
            'onboarding' => $onboarding,
            'steps' => $steps,
            'quotaBlocked' => $quotaBlocked,
            'latestAudit' => $latestAudit,
            'latestBacklinkRun' => $latestBacklinkRun,
            'latestInsightRun' => $latestInsightRun,
            'googleIntegration' => $googleIntegration,
            'metaConnector' => $metaConnector,
            'latestReport' => $latestReport,
        ]);
    }

    /**
     * Sync steps from real data
     */
    protected function syncSteps(Domain $domain, DomainOnboarding $onboarding): array
    {
        $steps = $onboarding->steps_json ?? [];

        // Step 1: Domain added (always done if onboarding exists)
        $steps[DomainOnboarding::STEP_DOMAIN_ADDED] = [
            'done' => true,
            'at' => $domain->created_at->toIso8601String(),
        ];

        // Step 2: Audit started
        $latestAudit = DomainAudit::where('domain_id', $domain->id)->latest()->first();
        if ($latestAudit) {
            $steps[DomainOnboarding::STEP_AUDIT_STARTED] = [
                'done' => true,
                'audit_id' => $latestAudit->id,
                'at' => $latestAudit->created_at->toIso8601String(),
            ];

            // Audit completed
            if ($latestAudit->status === DomainAudit::STATUS_COMPLETED) {
                $steps[DomainOnboarding::STEP_AUDIT_COMPLETED] = [
                    'done' => true,
                    'audit_id' => $latestAudit->id,
                    'at' => $latestAudit->finished_at?->toIso8601String() ?? $latestAudit->updated_at->toIso8601String(),
                ];
            } else {
                $steps[DomainOnboarding::STEP_AUDIT_COMPLETED] = ['done' => false];
            }
        } else {
            $steps[DomainOnboarding::STEP_AUDIT_STARTED] = ['done' => false];
            $steps[DomainOnboarding::STEP_AUDIT_COMPLETED] = ['done' => false];
        }

        // Step 3: Google connected
        $googleIntegration = $domain->googleIntegration;
        if ($googleIntegration && $googleIntegration->connectedAccount) {
            $steps[DomainOnboarding::STEP_GOOGLE_CONNECTED] = [
                'done' => true,
                'at' => $googleIntegration->created_at->toIso8601String(),
            ];

            // Google properties selected
            if ($googleIntegration->gsc_property || $googleIntegration->ga4_property_id) {
                $steps[DomainOnboarding::STEP_GOOGLE_SELECTED] = [
                    'done' => true,
                    'at' => $googleIntegration->updated_at->toIso8601String(),
                ];
            } else {
                $steps[DomainOnboarding::STEP_GOOGLE_SELECTED] = ['done' => false];
            }
        } else {
            $steps[DomainOnboarding::STEP_GOOGLE_CONNECTED] = ['done' => false];
            $steps[DomainOnboarding::STEP_GOOGLE_SELECTED] = ['done' => false];
        }

        // Step 4: Backlinks started
        $latestBacklinkRun = DomainBacklinkRun::where('domain_id', $domain->id)->latest()->first();
        if ($latestBacklinkRun) {
            $steps[DomainOnboarding::STEP_BACKLINKS_STARTED] = [
                'done' => true,
                'run_id' => $latestBacklinkRun->id,
                'at' => $latestBacklinkRun->created_at->toIso8601String(),
            ];

            // Backlinks completed
            if ($latestBacklinkRun->status === DomainBacklinkRun::STATUS_COMPLETED) {
                $steps[DomainOnboarding::STEP_BACKLINKS_COMPLETED] = [
                    'done' => true,
                    'run_id' => $latestBacklinkRun->id,
                    'at' => $latestBacklinkRun->finished_at?->toIso8601String() ?? $latestBacklinkRun->updated_at->toIso8601String(),
                ];
            } else {
                $steps[DomainOnboarding::STEP_BACKLINKS_COMPLETED] = ['done' => false];
            }
        } else {
            $steps[DomainOnboarding::STEP_BACKLINKS_STARTED] = ['done' => false];
            $steps[DomainOnboarding::STEP_BACKLINKS_COMPLETED] = ['done' => false];
        }

        // Step 5: Meta connector
        $metaConnector = $domain->metaConnector;
        if ($metaConnector && $metaConnector->status === 'connected') {
            $steps[DomainOnboarding::STEP_META_CONNECTOR] = [
                'done' => true,
                'type' => $metaConnector->type,
                'at' => $metaConnector->updated_at->toIso8601String(),
            ];
        } else {
            $steps[DomainOnboarding::STEP_META_CONNECTOR] = ['done' => false];
        }

        // Step 6: Insights generated
        $latestInsightRun = DomainInsightRun::where('domain_id', $domain->id)->latest()->first();
        if ($latestInsightRun && $latestInsightRun->status === DomainInsightRun::STATUS_COMPLETED) {
            $steps[DomainOnboarding::STEP_INSIGHTS_GENERATED] = [
                'done' => true,
                'run_id' => $latestInsightRun->id,
                'at' => $latestInsightRun->finished_at?->toIso8601String() ?? $latestInsightRun->updated_at->toIso8601String(),
            ];
        } else {
            $steps[DomainOnboarding::STEP_INSIGHTS_GENERATED] = ['done' => false];
        }

        // Step 7: Report created (optional)
        $latestReport = PublicReport::where('domain_id', $domain->id)->latest()->first();
        if ($latestReport) {
            $steps[DomainOnboarding::STEP_REPORT_CREATED] = [
                'done' => true,
                'report_id' => $latestReport->id,
                'at' => $latestReport->created_at->toIso8601String(),
            ];
        } else {
            $steps[DomainOnboarding::STEP_REPORT_CREATED] = ['done' => false];
        }

        return $steps;
    }

    /**
     * Check quotas
     */
    protected function checkQuotas(Domain $domain, QuotaService $quotaService): array
    {
        $user = Auth::user();
        $blocked = [];

        // Audit quota
        try {
            $quotaService->assertCan($user, 'audits.runs_per_month', 1);
        } catch (\App\Exceptions\QuotaExceededException $e) {
            $blocked['audits'] = [
                'blocked' => true,
                'reason' => $e->getMessage(),
                'resetDate' => $e->resetDate?->toIso8601String(),
            ];
        }

        // Backlinks quota
        try {
            $quotaService->assertCan($user, 'backlinks.runs_per_month', 1);
        } catch (\App\Exceptions\QuotaExceededException $e) {
            $blocked['backlinks'] = [
                'blocked' => true,
                'reason' => $e->getMessage(),
                'resetDate' => $e->resetDate?->toIso8601String(),
            ];
        }

        // Insights quota
        try {
            $quotaService->assertCan($user, 'insights.runs_per_day', 1);
        } catch (\App\Exceptions\QuotaExceededException $e) {
            $blocked['insights'] = [
                'blocked' => true,
                'reason' => $e->getMessage(),
                'resetDate' => $e->resetDate?->toIso8601String(),
            ];
        }

        return $blocked;
    }

    /**
     * Start audit
     */
    public function startAudit(Request $request, Domain $domain)
    {
        Gate::authorize('analyzer.run', $domain);

        $activityLogger = app(ActivityLogger::class);
        $activityLogger->info('system', 'setup_step_started', 'Setup step started: audit', Auth::id(), $domain->id, [
            'step' => 'audit',
        ]);

        // Call existing audit controller
        $auditController = new \App\Http\Controllers\DomainAuditController();
        $response = $auditController->store($request, $domain);

        // Update onboarding
        $onboarding = DomainOnboarding::firstOrCreate(
            ['domain_id' => $domain->id],
            ['user_id' => Auth::id(), 'status' => DomainOnboarding::STATUS_IN_PROGRESS, 'steps_json' => []]
        );

        $latestAudit = DomainAudit::where('domain_id', $domain->id)->latest()->first();
        if ($latestAudit) {
            $onboarding->markStepDone(DomainOnboarding::STEP_AUDIT_STARTED, ['audit_id' => $latestAudit->id]);
        }

        return $response;
    }

    /**
     * Connect Google (redirect to OAuth)
     */
    public function connectGoogle(Domain $domain)
    {
        Gate::authorize('google.connect', $domain);

        $activityLogger = app(ActivityLogger::class);
        $activityLogger->info('system', 'setup_step_started', 'Setup step started: google_connect', Auth::id(), $domain->id, [
            'step' => 'google_connect',
        ]);

        // Redirect to Google SEO OAuth with return URL
        return redirect()->route('domains.integrations.google.connect', [
            'domain' => $domain->id,
            'return_url' => route('domains.setup.show', $domain->id),
        ]);
    }

    /**
     * Save Google selection
     */
    public function saveGoogleSelection(Request $request, Domain $domain)
    {
        Gate::authorize('google.connect', $domain);

        // Call existing controller
        $integrationController = new \App\Http\Controllers\DomainGoogleIntegrationController();
        $response = $integrationController->saveSelection($request, $domain);

        // Update onboarding
        $onboarding = DomainOnboarding::firstOrCreate(
            ['domain_id' => $domain->id],
            ['user_id' => Auth::id(), 'status' => DomainOnboarding::STATUS_IN_PROGRESS, 'steps_json' => []]
        );

        $googleIntegration = $domain->fresh()->googleIntegration;
        if ($googleIntegration && ($googleIntegration->gsc_property || $googleIntegration->ga4_property_id)) {
            $onboarding->markStepDone(DomainOnboarding::STEP_GOOGLE_SELECTED);
        }

        $activityLogger = app(ActivityLogger::class);
        $activityLogger->success('system', 'setup_step_completed', 'Setup step completed: google_selected', Auth::id(), $domain->id, [
            'step' => 'google_selected',
        ]);

        return $response;
    }

    /**
     * Start backlinks
     */
    public function startBacklinks(Request $request, Domain $domain)
    {
        Gate::authorize('backlinks.run', $domain);

        $activityLogger = app(ActivityLogger::class);
        $activityLogger->info('system', 'setup_step_started', 'Setup step started: backlinks', Auth::id(), $domain->id, [
            'step' => 'backlinks',
        ]);

        // Call existing controller
        $backlinksController = new \App\Http\Controllers\DomainBacklinksController();
        $response = $backlinksController->store($request, $domain);

        // Update onboarding
        $onboarding = DomainOnboarding::firstOrCreate(
            ['domain_id' => $domain->id],
            ['user_id' => Auth::id(), 'status' => DomainOnboarding::STATUS_IN_PROGRESS, 'steps_json' => []]
        );

        $latestRun = DomainBacklinkRun::where('domain_id', $domain->id)->latest()->first();
        if ($latestRun) {
            $onboarding->markStepDone(DomainOnboarding::STEP_BACKLINKS_STARTED, ['run_id' => $latestRun->id]);
        }

        return $response;
    }

    /**
     * Save meta connector
     */
    public function saveMetaConnector(Request $request, Domain $domain)
    {
        Gate::authorize('meta.edit', $domain);

        $activityLogger = app(ActivityLogger::class);
        $activityLogger->info('system', 'setup_step_started', 'Setup step started: meta_connector', Auth::id(), $domain->id, [
            'step' => 'meta_connector',
        ]);

        // Call existing controller
        $metaController = new \App\Http\Controllers\DomainMetaConnectorController();
        $response = $metaController->connectOrUpdate($request, $domain);

        // Update onboarding
        $onboarding = DomainOnboarding::firstOrCreate(
            ['domain_id' => $domain->id],
            ['user_id' => Auth::id(), 'status' => DomainOnboarding::STATUS_IN_PROGRESS, 'steps_json' => []]
        );

        $metaConnector = $domain->fresh()->metaConnector;
        if ($metaConnector && $metaConnector->status === 'connected') {
            $onboarding->markStepDone(DomainOnboarding::STEP_META_CONNECTOR, ['type' => $metaConnector->type]);
        }

        $activityLogger = app(ActivityLogger::class);
        $activityLogger->success('system', 'setup_step_completed', 'Setup step completed: meta_connector', Auth::id(), $domain->id, [
            'step' => 'meta_connector',
        ]);

        return $response;
    }

    /**
     * Run insights
     */
    public function runInsights(Domain $domain)
    {
        Gate::authorize('insights.run', $domain);

        $activityLogger = app(ActivityLogger::class);
        $activityLogger->info('system', 'setup_step_started', 'Setup step started: insights', Auth::id(), $domain->id, [
            'step' => 'insights',
        ]);

        // Call existing controller
        $insightsController = new \App\Http\Controllers\DomainInsightsController();
        $response = $insightsController->runNow($domain);

        return $response;
    }

    /**
     * Create report
     */
    public function createReport(Request $request, Domain $domain)
    {
        Gate::authorize('reports.manage', $domain);

        $activityLogger = app(ActivityLogger::class);
        $activityLogger->info('system', 'setup_step_started', 'Setup step started: report', Auth::id(), $domain->id, [
            'step' => 'report',
        ]);

        // Create report with default settings
        $reportData = array_merge($request->all(), [
            'sections' => [
                'analyzer' => true,
                'google' => true,
                'backlinks' => true,
                'meta' => true,
                'insights' => true,
            ],
        ]);

        // Call existing controller
        $reportsController = new \App\Http\Controllers\DomainReportsController();
        $response = $reportsController->store($request->merge($reportData), $domain);

        // Update onboarding
        $onboarding = DomainOnboarding::firstOrCreate(
            ['domain_id' => $domain->id],
            ['user_id' => Auth::id(), 'status' => DomainOnboarding::STATUS_IN_PROGRESS, 'steps_json' => []]
        );

        $latestReport = PublicReport::where('domain_id', $domain->id)->latest()->first();
        if ($latestReport) {
            $onboarding->markStepDone(DomainOnboarding::STEP_REPORT_CREATED, ['report_id' => $latestReport->id]);
        }

        $activityLogger = app(ActivityLogger::class);
        $activityLogger->success('system', 'setup_step_completed', 'Setup step completed: report', Auth::id(), $domain->id, [
            'step' => 'report',
        ]);

        return $response;
    }

    /**
     * Complete onboarding
     */
    public function complete(Domain $domain)
    {
        Gate::authorize('domain.view', $domain);

        $onboarding = DomainOnboarding::where('domain_id', $domain->id)->firstOrFail();
        $onboarding->update([
            'status' => DomainOnboarding::STATUS_COMPLETED,
            'current_step' => null,
        ]);

        $activityLogger = app(ActivityLogger::class);
        $activityLogger->success('system', 'setup_completed', 'Domain setup completed', Auth::id(), $domain->id);

        return redirect()->route('domains.show', $domain->id)
            ->with('success', 'Domain setup completed!');
    }
}
