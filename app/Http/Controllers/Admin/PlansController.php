<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PlansController extends Controller
{
    public function index(Request $request)
    {
        $plans = Plan::ordered()->get();

        return Inertia::render('Admin/Plans/Index', [
            'plans' => $plans->map(fn($plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'code' => $plan->code,
                'tagline' => $plan->tagline,
                'price_monthly' => $plan->monthly_price,
                'price_annual' => $plan->annual_price,
                'is_active' => $plan->is_active,
                'is_public' => $plan->is_public,
                'is_highlighted' => $plan->is_highlighted,
                'badge' => $plan->badge,
                'sort_order' => $plan->sort_order,
                'limits' => $plan->limits_json,
                'display_limits' => $plan->display_limits,
                'features' => $plan->features_json,
                'includes' => $plan->includes,
                'subscribers_count' => $plan->subscriptions()->count(),
            ]),
            'total' => Plan::count(),
        ]);
    }

    public function show($id)
    {
        $plan = Plan::findOrFail($id);

        return Inertia::render('Admin/Plans/Show', [
            'plan' => [
                'id' => $plan->id,
                'name' => $plan->name,
                'code' => $plan->code,
                'tagline' => $plan->tagline,
                'price_monthly' => $plan->monthly_price,
                'price_annual' => $plan->annual_price,
                'is_active' => $plan->is_active,
                'is_public' => $plan->is_public,
                'is_highlighted' => $plan->is_highlighted,
                'badge' => $plan->badge,
                'sort_order' => $plan->sort_order,
                'limits_json' => $plan->limits_json,
                'display_limits' => $plan->display_limits,
                'features_json' => $plan->features_json,
                'includes' => $plan->includes,
                'cta_primary_label' => $plan->cta_primary_label,
                'cta_primary_href' => $plan->cta_primary_href,
                'cta_secondary_label' => $plan->cta_secondary_label,
                'cta_secondary_href' => $plan->cta_secondary_href,
                'subscribers_count' => $plan->subscriptions()->count(),
                'created_at' => $plan->created_at,
                'updated_at' => $plan->updated_at,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Plans/Create', [
            'defaultLimits' => [
                'projects' => 1,
                'monthly_actions' => 1000,
                'team_seats' => 1,
                'domains.max_active' => 5,
                'audits.runs_per_month' => 10,
                'audits.pages_per_month' => 1000,
                'backlinks.runs_per_month' => 5,
                'backlinks.links_fetched_per_month' => 10000,
            ],
            'defaultFeatures' => [
                'website_analyzer' => true,
                'google_integrations' => true,
                'backlinks_checker' => true,
                'meta_editor' => true,
                'insights' => true,
                'comment_workflow' => true,
                'profile_workflow' => true,
                'forum_workflow' => false,
                'guest_workflow' => false,
                'approvals' => true,
                'evidence_logs' => true,
                'backlink_types' => ['comment', 'profile'],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:plans,code',
            'tagline' => 'nullable|string|max:500',
            'price_monthly' => 'nullable|numeric|min:0',
            'price_annual' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
            'is_highlighted' => 'boolean',
            'badge' => 'nullable|string|max:50',
            'sort_order' => 'integer|min:0',
            'limits_json' => 'nullable|array',
            'display_limits' => 'nullable|array',
            'features_json' => 'nullable|array',
            'includes' => 'nullable|array',
            'cta_primary_label' => 'nullable|string|max:100',
            'cta_primary_href' => 'nullable|string|max:255',
            'cta_secondary_label' => 'nullable|string|max:100',
            'cta_secondary_href' => 'nullable|string|max:255',
        ]);

        // Convert prices from dollars to cents
        if (isset($validated['price_monthly']) && $validated['price_monthly'] !== null) {
            $validated['price_monthly'] = (int) ($validated['price_monthly'] * 100);
        }
        if (isset($validated['price_annual']) && $validated['price_annual'] !== null) {
            $validated['price_annual'] = (int) ($validated['price_annual'] * 100);
        }

        $plan = Plan::create($validated);

        return redirect()->route('admin.plans.index')
            ->with('success', 'Plan created successfully.');
    }

    public function edit($id)
    {
        $plan = Plan::findOrFail($id);

        return Inertia::render('Admin/Plans/Edit', [
            'plan' => [
                'id' => $plan->id,
                'name' => $plan->name,
                'code' => $plan->code,
                'tagline' => $plan->tagline,
                'price_monthly' => $plan->monthly_price,
                'price_annual' => $plan->annual_price,
                'is_active' => $plan->is_active,
                'is_public' => $plan->is_public,
                'is_highlighted' => $plan->is_highlighted,
                'badge' => $plan->badge,
                'sort_order' => $plan->sort_order,
                'limits_json' => $plan->limits_json,
                'display_limits' => $plan->display_limits,
                'features_json' => $plan->features_json,
                'includes' => $plan->includes,
                'cta_primary_label' => $plan->cta_primary_label,
                'cta_primary_href' => $plan->cta_primary_href,
                'cta_secondary_label' => $plan->cta_secondary_label,
                'cta_secondary_href' => $plan->cta_secondary_href,
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $plan = Plan::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:plans,code,' . $plan->id,
            'tagline' => 'nullable|string|max:500',
            'price_monthly' => 'nullable|numeric|min:0',
            'price_annual' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
            'is_highlighted' => 'boolean',
            'badge' => 'nullable|string|max:50',
            'sort_order' => 'integer|min:0',
            'limits_json' => 'nullable|array',
            'display_limits' => 'nullable|array',
            'features_json' => 'nullable|array',
            'includes' => 'nullable|array',
            'cta_primary_label' => 'nullable|string|max:100',
            'cta_primary_href' => 'nullable|string|max:255',
            'cta_secondary_label' => 'nullable|string|max:100',
            'cta_secondary_href' => 'nullable|string|max:255',
        ]);

        // Convert prices from dollars to cents
        if (isset($validated['price_monthly']) && $validated['price_monthly'] !== null) {
            $validated['price_monthly'] = (int) ($validated['price_monthly'] * 100);
        }
        if (isset($validated['price_annual']) && $validated['price_annual'] !== null) {
            $validated['price_annual'] = (int) ($validated['price_annual'] * 100);
        }

        $plan->update($validated);

        return redirect()->route('admin.plans.show', $plan->id)
            ->with('success', 'Plan updated successfully.');
    }

    public function destroy($id)
    {
        $plan = Plan::findOrFail($id);
        
        // Check if plan has subscribers
        if ($plan->subscriptions()->exists()) {
            return back()->with('error', 'Cannot delete plan with active subscribers. Please reassign users first.');
        }

        $plan->delete();

        return redirect()->route('admin.plans.index')
            ->with('success', 'Plan deleted successfully.');
    }

    /**
     * Toggle plan active status.
     */
    public function toggleActive($id)
    {
        $plan = Plan::findOrFail($id);
        $plan->update(['is_active' => !$plan->is_active]);

        return back()->with('success', 'Plan status updated.');
    }

    /**
     * Toggle plan public/visible status.
     */
    public function togglePublic($id)
    {
        $plan = Plan::findOrFail($id);
        $plan->update(['is_public' => !$plan->is_public]);

        return back()->with('success', 'Plan visibility updated.');
    }

    /**
     * Reorder plans.
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'plans' => 'required|array',
            'plans.*.id' => 'required|exists:plans,id',
            'plans.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($validated['plans'] as $planData) {
            Plan::where('id', $planData['id'])->update(['sort_order' => $planData['sort_order']]);
        }

        return back()->with('success', 'Plan order updated.');
    }
}
