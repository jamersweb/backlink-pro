<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\User;
use App\Models\Domain;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        $query = Campaign::with(['user:id,name,email', 'domain:id,name'])
            ->withCount(['backlinks', 'automationTasks'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by user
        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('web_name', 'like', "%{$search}%")
                  ->orWhere('web_url', 'like', "%{$search}%");
            });
        }

        $campaigns = $query->paginate(20)->withQueryString();

        // Get stats
        $stats = [
            'total' => Campaign::count(),
            'active' => Campaign::where('status', Campaign::STATUS_ACTIVE)->count(),
            'paused' => Campaign::where('status', Campaign::STATUS_PAUSED)->count(),
            'completed' => Campaign::where('status', Campaign::STATUS_COMPLETED)->count(),
            'error' => Campaign::where('status', Campaign::STATUS_ERROR)->count(),
        ];

        // Get users for filter
        $users = User::select('id', 'name', 'email')->orderBy('name')->get();

        return Inertia::render('Admin/Campaigns/Index', [
            'campaigns' => $campaigns,
            'stats' => $stats,
            'users' => $users,
            'filters' => $request->only(['status', 'user_id', 'search']),
        ]);
    }

    public function show(Campaign $campaign)
    {
        $campaign->load([
            'user:id,name,email',
            'domain:id,name',
            'gmailAccount:id,email,status',
            'backlinks' => function($query) {
                $query->latest()->limit(20);
            },
            'automationTasks' => function($query) {
                $query->latest()->limit(20);
            },
            'siteAccounts' => function($query) {
                $query->latest()->limit(20);
            },
        ]);

        $campaign->loadCount(['backlinks', 'automationTasks', 'siteAccounts']);

        // Get backlink stats
        $backlinkStats = [
            'total' => $campaign->backlinks()->count(),
            'verified' => $campaign->backlinks()->where('status', 'verified')->count(),
            'pending' => $campaign->backlinks()->where('status', 'pending')->count(),
            'failed' => $campaign->backlinks()->where('status', 'failed')->count(),
            'today' => $campaign->backlinks()->whereDate('created_at', today())->count(),
        ];

        // Get task stats
        $taskStats = [
            'total' => $campaign->automationTasks()->count(),
            'pending' => $campaign->automationTasks()->where('status', 'pending')->count(),
            'running' => $campaign->automationTasks()->where('status', 'running')->count(),
            'success' => $campaign->automationTasks()->where('status', 'success')->count(),
            'failed' => $campaign->automationTasks()->where('status', 'failed')->count(),
        ];

        return Inertia::render('Admin/Campaigns/Show', [
            'campaign' => $campaign,
            'backlinkStats' => $backlinkStats,
            'taskStats' => $taskStats,
        ]);
    }

    public function edit(Campaign $campaign)
    {
        $campaign->load(['user:id,name,email', 'domain:id,name', 'gmailAccount:id,email']);
        
        $users = User::select('id', 'name', 'email')->orderBy('name')->get();
        $domains = Domain::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Admin/Campaigns/Edit', [
            'campaign' => $campaign,
            'users' => $users,
            'domains' => $domains,
        ]);
    }

    public function update(Request $request, Campaign $campaign)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|in:active,inactive,paused,completed,error',
            'user_id' => 'required|exists:users,id',
            'domain_id' => 'nullable|exists:domains,id',
            'daily_limit' => 'nullable|integer|min:1',
            'total_limit' => 'nullable|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        $campaign->update($validated);

        return redirect()->route('admin.campaigns.show', $campaign)
            ->with('success', 'Campaign updated successfully.');
    }

    public function destroy(Campaign $campaign)
    {
        $campaign->delete();

        return redirect()->route('admin.campaigns.index')
            ->with('success', 'Campaign deleted successfully.');
    }

    public function pause(Campaign $campaign)
    {
        $campaign->update(['status' => Campaign::STATUS_PAUSED]);

        return back()->with('success', 'Campaign paused successfully.');
    }

    public function resume(Campaign $campaign)
    {
        $campaign->update(['status' => Campaign::STATUS_ACTIVE]);

        return back()->with('success', 'Campaign resumed successfully.');
    }
}