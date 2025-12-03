<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DomainController extends Controller
{
    /**
     * List all domains
     */
    public function index()
    {
        $domains = Domain::where('user_id', Auth::id())
            ->withCount('campaigns')
            ->with(['campaigns' => function($query) {
                $query->withCount('backlinks');
            }])
            ->latest()
            ->get()
            ->map(function($domain) {
                $domain->total_backlinks = $domain->campaigns->sum('backlinks_count');
                return $domain;
            });

        // Get user's plan limits
        $user = Auth::user();
        $plan = $user->plan;
        $stats = [
            'total_domains' => $domains->count(),
            'max_domains' => $plan ? ($plan->max_domains === -1 ? null : $plan->max_domains) : null,
            'can_add_more' => $plan ? ($plan->max_domains === -1 || $domains->count() < $plan->max_domains) : true,
        ];

        return Inertia::render('Domains/Index', [
            'domains' => $domains,
            'stats' => $stats,
        ]);
    }

    /**
     * Show create form
     */
    public function create()
    {
        return Inertia::render('Domains/Create');
    }

    /**
     * Store new domain
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'default_settings' => 'nullable|array',
            'status' => 'nullable|in:active,inactive',
        ]);

        // Check plan limits
        $user = Auth::user();
        $plan = $user->plan;
        
        if ($plan) {
            // Check max domains limit (-1 means unlimited)
            if ($plan->max_domains !== -1) {
                $userDomainCount = Domain::where('user_id', $user->id)->count();
                if ($userDomainCount >= $plan->max_domains) {
                    return back()->withErrors([
                        'domain_limit' => "You have reached your plan's maximum domain limit ({$plan->max_domains}). Please upgrade your plan or delete an existing domain to add a new one."
                    ])->withInput();
                }
            }
        } else {
            return back()->withErrors([
                'plan' => 'You need an active plan to add domains. Please subscribe to a plan first.'
            ])->withInput();
        }

        $domain = Domain::create([
            'user_id' => Auth::id(),
            'name' => $validated['name'],
            'default_settings' => $validated['default_settings'] ?? [],
            'status' => $validated['status'] ?? Domain::STATUS_ACTIVE,
        ]);

        return redirect()->route('domains.index')
            ->with('success', 'Domain created successfully');
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $domain = Domain::where('user_id', Auth::id())
            ->findOrFail($id);

        return Inertia::render('Domains/Edit', [
            'domain' => $domain,
        ]);
    }

    /**
     * Update domain
     */
    public function update(Request $request, $id)
    {
        $domain = Domain::where('user_id', Auth::id())
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'default_settings' => 'nullable|array',
            'status' => 'nullable|in:active,inactive',
        ]);

        $domain->update($validated);

        return redirect()->route('domains.index')
            ->with('success', 'Domain updated successfully');
    }

    /**
     * Delete domain
     */
    public function destroy($id)
    {
        $domain = Domain::where('user_id', Auth::id())
            ->findOrFail($id);

        $domain->delete();

        return redirect()->route('domains.index')
            ->with('success', 'Domain deleted successfully');
    }
}

