<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Campaign;
use App\Models\Backlink;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PlansController extends Controller
{
    public function index(Request $request)
    {
        $plans = Plan::orderBy('sort_order')
            ->get();

        return Inertia::render('Admin/Plans/Index', [
            'plans' => $plans,
            'total' => Plan::count(),
        ]);
    }

    public function show($id)
    {
        $plan = Plan::withCount('users')
            ->with(['users' => function($query) {
                $query->latest()->limit(10);
            }])
            ->findOrFail($id);

        // Get plan statistics
        $stats = [
            'total_subscribers' => $plan->users()->count(),
            'active_subscribers' => $plan->users()->where('subscription_status', 'active')->count(),
            'total_campaigns' => Campaign::whereHas('user', function($q) use ($plan) {
                $q->where('plan_id', $plan->id);
            })->count(),
            'total_backlinks' => Backlink::whereHas('campaign.user', function($q) use ($plan) {
                $q->where('plan_id', $plan->id);
            })->count(),
        ];

        return Inertia::render('Admin/Plans/Show', [
            'plan' => $plan,
            'stats' => $stats,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Plans/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:plans,slug',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'billing_interval' => 'required|in:monthly,yearly',
            'max_domains' => 'required|integer|min:-1',
            'max_campaigns' => 'required|integer|min:-1',
            'daily_backlink_limit' => 'required|integer|min:-1',
            'backlink_types' => 'nullable|array',
            'backlink_types.*' => 'in:comment,profile,forum,guestposting',
            'features' => 'nullable|array',
            'features.*' => 'string',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        Plan::create($validated);

        return redirect()->route('admin.plans.index')
            ->with('success', 'Plan created successfully.');
    }

    public function edit($id)
    {
        $plan = Plan::findOrFail($id);

        return Inertia::render('Admin/Plans/Edit', [
            'plan' => $plan,
        ]);
    }

    public function update(Request $request, $id)
    {
        $plan = Plan::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:plans,slug,' . $plan->id,
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'billing_interval' => 'required|in:monthly,yearly',
            'max_domains' => 'required|integer|min:-1',
            'max_campaigns' => 'required|integer|min:-1',
            'daily_backlink_limit' => 'required|integer|min:-1',
            'backlink_types' => 'nullable|array',
            'backlink_types.*' => 'in:comment,profile,forum,guestposting',
            'features' => 'nullable|array',
            'features.*' => 'string',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $plan->update($validated);

        return redirect()->route('admin.plans.show', $plan)
            ->with('success', 'Plan updated successfully.');
    }

    public function destroy($id)
    {
        $plan = Plan::findOrFail($id);
        
        // Check if plan has subscribers
        if ($plan->users()->count() > 0) {
            return back()->with('error', 'Cannot delete plan with active subscribers. Please reassign users first.');
        }

        $plan->delete();

        return redirect()->route('admin.plans.index')
            ->with('success', 'Plan deleted successfully.');
    }
}

