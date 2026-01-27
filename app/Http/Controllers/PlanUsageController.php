<?php

namespace App\Http\Controllers;

use App\Services\Usage\QuotaService;
use App\Models\Domain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;

class PlanUsageController extends Controller
{
    protected $quotaService;

    public function __construct(QuotaService $quotaService)
    {
        $this->quotaService = $quotaService;
    }

    /**
     * Show plan and usage page
     */
    public function index()
    {
        $user = Auth::user();
        $subscription = $this->quotaService->getSubscription($user);
        $plan = $subscription->plan;

        // Build usage data
        $usage = [];

        // Domains (absolute cap)
        $domainsUsed = Domain::where('user_id', $user->id)
            ->where('status', Domain::STATUS_ACTIVE)
            ->count();
        $domainsLimit = $plan->getLimit('domains.max_active');
        $usage['domains'] = [
            'used' => $domainsUsed,
            'limit' => $domainsLimit,
            'reset_date' => null,
        ];

        // Audits runs (monthly)
        $auditsRunsUsed = $this->quotaService->getUsed($user, 'audits.runs_per_month', 'month');
        $auditsRunsLimit = $plan->getLimit('audits.runs_per_month');
        $usage['audits_runs'] = [
            'used' => $auditsRunsUsed,
            'limit' => $auditsRunsLimit,
            'reset_date' => $subscription->getResetDate(),
        ];

        // Audits pages (monthly)
        $auditsPagesUsed = $this->quotaService->getUsed($user, 'audits.pages_per_month', 'month');
        $auditsPagesLimit = $plan->getLimit('audits.pages_per_month');
        $usage['audits_pages'] = [
            'used' => $auditsPagesUsed,
            'limit' => $auditsPagesLimit,
            'reset_date' => $subscription->getResetDate(),
        ];

        // Backlinks runs (monthly)
        $backlinksRunsUsed = $this->quotaService->getUsed($user, 'backlinks.runs_per_month', 'month');
        $backlinksRunsLimit = $plan->getLimit('backlinks.runs_per_month');
        $usage['backlinks_runs'] = [
            'used' => $backlinksRunsUsed,
            'limit' => $backlinksRunsLimit,
            'reset_date' => $subscription->getResetDate(),
        ];

        // Backlinks links fetched (monthly)
        $backlinksLinksUsed = $this->quotaService->getUsed($user, 'backlinks.links_fetched_per_month', 'month');
        $backlinksLinksLimit = $plan->getLimit('backlinks.links_fetched_per_month');
        $usage['backlinks_links'] = [
            'used' => $backlinksLinksUsed,
            'limit' => $backlinksLinksLimit,
            'reset_date' => $subscription->getResetDate(),
        ];

        // Google sync now (daily)
        $googleSyncUsed = $this->quotaService->getUsed($user, 'google.sync_now_per_day', 'day');
        $googleSyncLimit = $plan->getLimit('google.sync_now_per_day');
        $usage['google_sync'] = [
            'used' => $googleSyncUsed,
            'limit' => $googleSyncLimit,
            'reset_date' => Carbon::tomorrow(),
        ];

        // Meta publishes (monthly)
        $metaPublishUsed = $this->quotaService->getUsed($user, 'meta.publish_per_month', 'month');
        $metaPublishLimit = $plan->getLimit('meta.publish_per_month');
        $usage['meta_publish'] = [
            'used' => $metaPublishUsed,
            'limit' => $metaPublishLimit,
            'reset_date' => $subscription->getResetDate(),
        ];

        // Insights runs (daily)
        $insightsRunsUsed = $this->quotaService->getUsed($user, 'insights.runs_per_day', 'day');
        $insightsRunsLimit = $plan->getLimit('insights.runs_per_day');
        $usage['insights_runs'] = [
            'used' => $insightsRunsUsed,
            'limit' => $insightsRunsLimit,
            'reset_date' => Carbon::tomorrow(),
        ];

        return Inertia::render('Settings/PlanUsage', [
            'subscription' => $subscription,
            'plan' => $plan,
            'usage' => $usage,
        ]);
    }
}
