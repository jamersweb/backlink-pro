<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\DomainPlan;
use App\Models\DomainTask;
use App\Jobs\Planner\GenerateDomainPlanJob;
use App\Services\Planner\DomainActionPlanner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class DomainPlannerController extends Controller
{
    /**
     * Show planner page
     */
    public function index(Domain $domain)
    {
        Gate::authorize('insights.view', $domain);

        $latestPlan = DomainPlan::where('domain_id', $domain->id)
            ->where('status', DomainPlan::STATUS_DRAFT)
            ->latest()
            ->first();

        // Get tasks grouped by planner_group
        $tasks = DomainTask::where('domain_id', $domain->id)
            ->whereIn('status', [DomainTask::STATUS_OPEN, DomainTask::STATUS_DOING])
            ->whereNotNull('planner_group')
            ->orderBy('impact_score', 'desc')
            ->get()
            ->groupBy('planner_group');

        return Inertia::render('Domains/Planner/Index', [
            'domain' => $domain,
            'plan' => $latestPlan,
            'tasks' => [
                'today' => $tasks->get('today', collect()),
                'week' => $tasks->get('week', collect()),
                'month' => $tasks->get('month', collect()),
            ],
        ]);
    }

    /**
     * Generate plan
     */
    public function generate(Request $request, Domain $domain)
    {
        Gate::authorize('insights.run', $domain);

        $periodDays = $request->input('period_days', 28);

        GenerateDomainPlanJob::dispatch($domain->id, Auth::id(), $periodDays)
            ->onQueue('insights');

        return back()->with('success', 'Plan generation started. Refresh in a few moments.');
    }

    /**
     * Apply plan
     */
    public function apply(Request $request, Domain $domain)
    {
        Gate::authorize('insights.run', $domain);

        $plan = DomainPlan::where('domain_id', $domain->id)
            ->where('status', DomainPlan::STATUS_DRAFT)
            ->latest()
            ->firstOrFail();

        $planner = new DomainActionPlanner($domain, $plan->period_days);
        $result = $planner->applyPlan($plan->plan_json, Auth::id());

        $plan->update([
            'status' => DomainPlan::STATUS_APPLIED,
            'applied_at' => now(),
        ]);

        return back()->with('success', "Plan applied: {$result['created']} tasks created, {$result['updated']} tasks updated.");
    }

    /**
     * Archive plan
     */
    public function archive(Domain $domain, DomainPlan $plan)
    {
        Gate::authorize('insights.view', $domain);

        if ($plan->domain_id !== $domain->id) {
            abort(403);
        }

        $plan->update(['status' => DomainPlan::STATUS_ARCHIVED]);

        return back()->with('success', 'Plan archived');
    }
}
