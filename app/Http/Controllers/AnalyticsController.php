<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\OrgMetricsDaily;
use App\Models\UsageEvent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Show retention dashboard
     */
    public function retention(Request $request)
    {
        // TODO: Add admin authorization
        
        $dateRange = $request->input('date_range', '30');
        $startDate = now()->subDays($dateRange)->startOfDay();
        $endDate = now()->endOfDay();

        // Activation funnel
        $totalOrgs = Organization::whereBetween('created_at', [$startDate, $endDate])->count();
        $createdFirstAudit = Organization::whereBetween('created_at', [$startDate, $endDate])
            ->has('audits')
            ->count();
        $completedFirstAudit = Organization::whereBetween('created_at', [$startDate, $endDate])
            ->whereHas('audits', function ($query) {
                $query->where('status', 'completed');
            })
            ->count();
        $exportedPdf = Organization::whereBetween('created_at', [$startDate, $endDate])
            ->whereHas('audits', function ($query) {
                $query->whereNotNull('finished_at');
            })
            ->count();

        $funnel = [
            'total_orgs' => $totalOrgs,
            'created_first_audit' => $createdFirstAudit,
            'completed_first_audit' => $completedFirstAudit,
            'exported_pdf' => $exportedPdf,
        ];

        // Usage trends
        $dailyMetrics = OrgMetricsDaily::whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->select(
                DB::raw('DATE(date) as date'),
                DB::raw('SUM(audits_created) as audits_created'),
                DB::raw('SUM(pages_crawled) as pages_crawled'),
                DB::raw('SUM(lighthouse_runs) as lighthouse_runs'),
                DB::raw('SUM(leads_generated) as leads_generated')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // At-risk organizations
        $atRiskOrgs = Organization::where('plan_status', 'past_due')
            ->orWhere(function ($query) {
                $query->where('plan_key', '!=', 'free')
                    ->whereDoesntHave('audits', function ($q) {
                        $q->where('created_at', '>', now()->subDays(14));
                    });
            })
            ->with('owner')
            ->limit(50)
            ->get()
            ->map(function ($org) {
                return [
                    'id' => $org->id,
                    'name' => $org->name,
                    'plan_key' => $org->plan_key,
                    'plan_status' => $org->plan_status,
                    'owner' => $org->owner->name ?? 'N/A',
                    'last_audit' => $org->audits()->latest()->first()?->created_at?->diffForHumans(),
                ];
            });

        return Inertia::render('Analytics/Retention', [
            'funnel' => $funnel,
            'dailyMetrics' => $dailyMetrics,
            'atRiskOrgs' => $atRiskOrgs,
            'dateRange' => $dateRange,
        ]);
    }

    /**
     * Show organization insights
     */
    public function insights(Request $request, Organization $organization)
    {
        $this->authorize('view', $organization);

        // Last 30 days usage
        $last30Days = OrgMetricsDaily::where('organization_id', $organization->id)
            ->where('date', '>=', now()->subDays(30)->format('Y-m-d'))
            ->orderBy('date')
            ->get();

        // Activation status
        $hasCreatedAudit = $organization->audits()->exists();
        $hasCompletedAudit = $organization->audits()->where('status', 'completed')->exists();
        $hasExportedPdf = UsageEvent::where('organization_id', $organization->id)
            ->where('event_type', 'pdf_export')
            ->exists();
        $hasUsedWidget = UsageEvent::where('organization_id', $organization->id)
            ->where('event_type', 'widget_audit_created')
            ->exists();
        $hasInvitedTeam = $organization->invitations()->whereNotNull('accepted_at')->exists();

        $activation = [
            'created_first_audit' => $hasCreatedAudit,
            'completed_first_audit' => $hasCompletedAudit,
            'exported_pdf' => $hasExportedPdf,
            'used_widget' => $hasUsedWidget,
            'invited_team' => $hasInvitedTeam,
        ];

        // Engagement metrics (last 7 days)
        $last7Days = OrgMetricsDaily::where('organization_id', $organization->id)
            ->where('date', '>=', now()->subDays(7)->format('Y-m-d'))
            ->sum('audits_created');

        // Churn risk signals
        $churnRisk = [];
        if ($organization->plan_key !== 'free') {
            $lastAudit = $organization->audits()->latest()->first();
            if (!$lastAudit || $lastAudit->created_at < now()->subDays(14)) {
                $churnRisk[] = 'no_audit_14_days';
            }
        }
        if ($organization->plan_status === 'past_due') {
            $churnRisk[] = 'past_due';
        }

        return Inertia::render('Organizations/Insights', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'last30Days' => $last30Days,
            'activation' => $activation,
            'last7DaysAudits' => $last7Days,
            'churnRisk' => $churnRisk,
        ]);
    }
}
