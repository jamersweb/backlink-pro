<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BacklinkOpportunity;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BacklinkOpportunityController extends Controller
{
    /**
     * Display a listing of backlink opportunities (campaign-specific)
     * Shows where user links were added
     */
    public function index(Request $request)
    {
        $query = BacklinkOpportunity::with(['campaign:id,name,user_id', 'campaign.user:id,name,email', 'backlink:id,url,pa,da,site_type', 'siteAccount:id,site_domain']);

        // Filter by campaign
        if ($request->has('campaign_id') && $request->campaign_id) {
            $query->where('campaign_id', $request->campaign_id);
        }

        // Filter by user
        if ($request->has('user_id') && $request->user_id) {
            $query->whereHas('campaign', function($q) use ($request) {
                $q->where('user_id', $request->user_id);
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        // Date range filter
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('url', 'like', "%{$search}%")
                  ->orWhere('keyword', 'like', "%{$search}%")
                  ->orWhere('anchor_text', 'like', "%{$search}%")
                  ->orWhereHas('backlink', function($q) use ($search) {
                      $q->where('url', 'like', "%{$search}%");
                  });
            });
        }

        $opportunities = $query->latest()->paginate(50)->withQueryString();

        // Get stats
        $stats = [
            'total' => BacklinkOpportunity::count(),
            'verified' => BacklinkOpportunity::where('status', BacklinkOpportunity::STATUS_VERIFIED)->count(),
            'pending' => BacklinkOpportunity::where('status', BacklinkOpportunity::STATUS_PENDING)->count(),
            'submitted' => BacklinkOpportunity::where('status', BacklinkOpportunity::STATUS_SUBMITTED)->count(),
            'error' => BacklinkOpportunity::where('status', BacklinkOpportunity::STATUS_FAILED)->count(),
            'today' => BacklinkOpportunity::whereDate('created_at', today())->count(),
            'this_week' => BacklinkOpportunity::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'this_month' => BacklinkOpportunity::whereMonth('created_at', now()->month)->count(),
        ];

        // Get filter options
        $campaigns = Campaign::select('id', 'name', 'user_id')
            ->with('user:id,name')
            ->orderBy('name')
            ->get();
        
        $users = User::select('id', 'name', 'email')->orderBy('name')->get();

        return Inertia::render('Admin/BacklinkOpportunities/Index', [
            'opportunities' => $opportunities,
            'stats' => $stats,
            'campaigns' => $campaigns,
            'users' => $users,
            'filters' => $request->only(['status', 'type', 'campaign_id', 'user_id', 'date_from', 'date_to', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new opportunity
     * Note: Opportunities are usually created automatically by Python worker
     * This is mainly for manual creation if needed
     */
    public function create()
    {
        $campaigns = Campaign::select('id', 'name', 'user_id')
            ->with('user:id,name')
            ->orderBy('name')
            ->get();
        
        $backlinks = \App\Models\Backlink::where('status', \App\Models\Backlink::STATUS_ACTIVE)
            ->select('id', 'url', 'pa', 'da', 'site_type')
            ->orderBy('url')
            ->limit(1000)
            ->get();

        return Inertia::render('Admin/BacklinkOpportunities/Create', [
            'campaigns' => $campaigns,
            'backlinks' => $backlinks,
        ]);
    }

    /**
     * Store a newly created opportunity
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'campaign_id' => 'required|exists:campaigns,id',
            'backlink_id' => 'required|exists:backlinks,id',
            'url' => 'nullable|url',
            'type' => 'required|in:comment,profile,forum,guestposting',
            'keyword' => 'nullable|string|max:255',
            'anchor_text' => 'nullable|string|max:255',
            'status' => 'required|in:pending,submitted,verified,error',
        ]);

        $backlink = \App\Models\Backlink::findOrFail($validated['backlink_id']);
        $actualUrl = $validated['url'] ?? $backlink->url;

        $opportunity = BacklinkOpportunity::create([
            'campaign_id' => $validated['campaign_id'],
            'backlink_id' => $validated['backlink_id'],
            'url' => $actualUrl,
            'type' => $validated['type'],
            'keyword' => $validated['keyword'] ?? null,
            'anchor_text' => $validated['anchor_text'] ?? null,
            'status' => $validated['status'],
            'verified_at' => $validated['status'] === 'verified' ? now() : null,
        ]);

        return redirect()->route('admin.backlink-opportunities.index')
            ->with('success', 'Backlink opportunity created successfully.');
    }

    /**
     * Show the form for editing an opportunity
     */
    public function edit($id)
    {
        $opportunity = BacklinkOpportunity::with(['campaign', 'backlink'])->findOrFail($id);
        
        $campaigns = Campaign::select('id', 'name', 'user_id')
            ->with('user:id,name')
            ->orderBy('name')
            ->get();
        
        $backlinks = \App\Models\Backlink::where('status', \App\Models\Backlink::STATUS_ACTIVE)
            ->select('id', 'url', 'pa', 'da', 'site_type')
            ->orderBy('url')
            ->limit(1000)
            ->get();

        return Inertia::render('Admin/BacklinkOpportunities/Edit', [
            'opportunity' => $opportunity,
            'campaigns' => $campaigns,
            'backlinks' => $backlinks,
        ]);
    }

    /**
     * Update the specified opportunity
     */
    public function update(Request $request, $id)
    {
        $opportunity = BacklinkOpportunity::findOrFail($id);

        $validated = $request->validate([
            'campaign_id' => 'required|exists:campaigns,id',
            'backlink_id' => 'required|exists:backlinks,id',
            'url' => 'nullable|url',
            'type' => 'required|in:comment,profile,forum,guestposting',
            'keyword' => 'nullable|string|max:255',
            'anchor_text' => 'nullable|string|max:255',
            'status' => 'required|in:pending,submitted,verified,error',
        ]);

        $opportunity->update([
            'campaign_id' => $validated['campaign_id'],
            'backlink_id' => $validated['backlink_id'],
            'url' => $validated['url'] ?? $opportunity->backlink->url,
            'type' => $validated['type'],
            'keyword' => $validated['keyword'] ?? null,
            'anchor_text' => $validated['anchor_text'] ?? null,
            'status' => $validated['status'],
            'verified_at' => $validated['status'] === 'verified' && !$opportunity->verified_at ? now() : $opportunity->verified_at,
        ]);

        return redirect()->route('admin.backlink-opportunities.index')
            ->with('success', 'Backlink opportunity updated successfully.');
    }

    /**
     * Remove the specified opportunity
     */
    public function destroy($id)
    {
        $opportunity = BacklinkOpportunity::findOrFail($id);
        $opportunity->delete();

        return redirect()->route('admin.backlink-opportunities.index')
            ->with('success', 'Backlink opportunity deleted successfully.');
    }

    /**
     * Bulk import - Not applicable for opportunities (they're created automatically)
     * Redirect to backlinks bulk import instead
     */
    public function bulkImport(Request $request)
    {
        return redirect()->route('admin.backlinks.index')
            ->with('info', 'To add sites to the store, use the Backlinks page bulk import feature.');
    }

    /**
     * Export opportunities
     */
    public function export(Request $request)
    {
        $query = BacklinkOpportunity::with(['campaign:id,name', 'campaign.user:id,name,email', 'backlink:id,url,pa,da']);

        // Apply filters
        if ($request->has('status') && $request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }
        if ($request->has('campaign_id') && $request->campaign_id) {
            $query->where('campaign_id', $request->campaign_id);
        }
        if ($request->has('user_id') && $request->user_id) {
            $query->whereHas('campaign', function($q) use ($request) {
                $q->where('user_id', $request->user_id);
            });
        }

        $opportunities = $query->get();

        $filename = 'backlink_opportunities_export_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($opportunities) {
            $file = fopen('php://output', 'w');
            
            // Header row
            fputcsv($file, [
                'ID',
                'Campaign',
                'User',
                'Backlink Store URL',
                'Actual URL',
                'Type',
                'Keyword',
                'Anchor Text',
                'PA',
                'DA',
                'Status',
                'Verified At',
                'Error Message',
                'Created At',
            ]);

            // Data rows
            foreach ($opportunities as $opp) {
                fputcsv($file, [
                    $opp->id,
                    $opp->campaign->name ?? 'N/A',
                    $opp->campaign->user->name ?? 'N/A',
                    $opp->backlink->url ?? 'N/A',
                    $opp->url ?? $opp->backlink->url ?? 'N/A',
                    $opp->type,
                    $opp->keyword ?? '',
                    $opp->anchor_text ?? '',
                    $opp->backlink->pa ?? '',
                    $opp->backlink->da ?? '',
                    $opp->status,
                    $opp->verified_at ? $opp->verified_at->format('Y-m-d H:i:s') : '',
                    $opp->error_message ?? '',
                    $opp->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
