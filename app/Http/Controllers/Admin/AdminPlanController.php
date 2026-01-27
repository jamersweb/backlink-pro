<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminPlanController extends Controller
{
    /**
     * Display a listing of plans.
     */
    public function index()
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
                'features' => $plan->features_json,
                'subscribers_count' => $plan->subscriptions()->count(),
            ]),
        ]);
    }

    /**
     * Show the form for creating a new plan.
     */
    public function create()
    {
        return Inertia::render('Admin/Plans/Create');
    }

    /**
     * Store a newly created plan.
     */
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
        if (isset($validated['price_monthly'])) {
            $validated['price_monthly'] = (int) ($validated['price_monthly'] * 100);
        }
        if (isset($validated['price_annual'])) {
            $validated['price_annual'] = (int) ($validated['price_annual'] * 100);
        }

        Plan::create($validated);

        return redirect()->route('admin.plans.index')
            ->with('success', 'Plan created successfully.');
    }

    /**
     * Display the specified plan.
     */
    public function show(Plan $plan)
    {
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

    /**
     * Show the form for editing the specified plan.
     */
    public function edit(Plan $plan)
    {
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

    /**
     * Update the specified plan.
     */
    public function update(Request $request, Plan $plan)
    {
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
        if (isset($validated['price_monthly'])) {
            $validated['price_monthly'] = (int) ($validated['price_monthly'] * 100);
        }
        if (isset($validated['price_annual'])) {
            $validated['price_annual'] = (int) ($validated['price_annual'] * 100);
        }

        $plan->update($validated);

        return redirect()->route('admin.plans.index')
            ->with('success', 'Plan updated successfully.');
    }

    /**
     * Remove the specified plan.
     */
    public function destroy(Plan $plan)
    {
        // Check if plan has active subscribers
        if ($plan->subscriptions()->exists()) {
            return back()->with('error', 'Cannot delete plan with active subscribers.');
        }

        $plan->delete();

        return redirect()->route('admin.plans.index')
            ->with('success', 'Plan deleted successfully.');
    }

    /**
     * Toggle plan active status.
     */
    public function toggleActive(Plan $plan)
    {
        $plan->update(['is_active' => !$plan->is_active]);

        return back()->with('success', 'Plan status updated.');
    }

    /**
     * Toggle plan public status.
     */
    public function togglePublic(Plan $plan)
    {
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
