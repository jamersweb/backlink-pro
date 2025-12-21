<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\BacklinkOpportunity;
use App\Jobs\VerifyBacklinkJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BacklinkController extends Controller
{
    /**
     * Show all user's backlink opportunities (where their links were added)
     */
    public function all(Request $request)
    {
        $campaignIds = Campaign::where('user_id', Auth::id())->pluck('id');
        
        $query = BacklinkOpportunity::whereIn('campaign_id', $campaignIds)
            ->with(['campaign:id,name,domain_id', 'campaign.domain:id,name', 'backlink:id,url,pa,da,site_type', 'siteAccount:id,username'])
            ->latest();

        // Apply filters
        if ($request->has('campaign_id') && $request->campaign_id) {
            $query->where('campaign_id', $request->campaign_id);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->has('search') && $request->search) {
            $query->where(function($q) use ($request) {
                $q->where('url', 'like', '%' . $request->search . '%')
                  ->orWhere('keyword', 'like', '%' . $request->search . '%')
                  ->orWhere('anchor_text', 'like', '%' . $request->search . '%')
                  ->orWhereHas('campaign', function($q) use ($request) {
                      $q->where('name', 'like', '%' . $request->search . '%');
                  })
                  ->orWhereHas('backlink', function($q) use ($request) {
                      $q->where('url', 'like', '%' . $request->search . '%');
                  });
            });
        }

        $opportunities = $query->paginate(25)->withQueryString();

        // Get statistics
        $stats = [
            'total' => BacklinkOpportunity::whereIn('campaign_id', $campaignIds)->count(),
            'verified' => BacklinkOpportunity::whereIn('campaign_id', $campaignIds)
                ->where('status', BacklinkOpportunity::STATUS_VERIFIED)->count(),
            'pending' => BacklinkOpportunity::whereIn('campaign_id', $campaignIds)
                ->where('status', BacklinkOpportunity::STATUS_PENDING)->count(),
            'submitted' => BacklinkOpportunity::whereIn('campaign_id', $campaignIds)
                ->where('status', BacklinkOpportunity::STATUS_SUBMITTED)->count(),
            'error' => BacklinkOpportunity::whereIn('campaign_id', $campaignIds)
                ->where('status', BacklinkOpportunity::STATUS_FAILED)->count(),
        ];

        // Get user's campaigns for filter dropdown
        $campaigns = Campaign::where('user_id', Auth::id())
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('Backlinks/Index', [
            'backlinks' => $opportunities,
            'stats' => $stats,
            'campaigns' => $campaigns,
            'filters' => $request->only(['campaign_id', 'status', 'type', 'date_from', 'date_to', 'search']),
        ]);
    }

    /**
     * Export backlink opportunities
     */
    public function export(Request $request)
    {
        $campaignIds = Campaign::where('user_id', Auth::id())->pluck('id');
        
        $query = BacklinkOpportunity::whereIn('campaign_id', $campaignIds)
            ->with(['campaign:id,name', 'campaign.domain:id,name', 'backlink:id,url,pa,da']);

        // Apply same filters as index
        if ($request->has('campaign_id') && $request->campaign_id) {
            $query->where('campaign_id', $request->campaign_id);
        }
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $opportunities = $query->get();
        $format = $request->get('format', 'csv'); // csv or json

        if ($format === 'json') {
            return response()->json($opportunities->map(function($opp) {
                return [
                    'id' => $opp->id,
                    'url' => $opp->url ?? $opp->backlink->url ?? '',
                    'type' => $opp->type,
                    'status' => $opp->status,
                    'keyword' => $opp->keyword,
                    'anchor_text' => $opp->anchor_text,
                    'campaign' => $opp->campaign->name ?? 'N/A',
                    'domain' => $opp->campaign->domain->name ?? 'N/A',
                    'pa' => $opp->backlink->pa ?? null,
                    'da' => $opp->backlink->da ?? null,
                    'created_at' => $opp->created_at->toISOString(),
                    'verified_at' => $opp->verified_at ? $opp->verified_at->toISOString() : null,
                ];
            }), 200, [
                'Content-Type' => 'application/json',
                'Content-Disposition' => 'attachment; filename="backlinks-' . now()->format('Y-m-d') . '.json"',
            ]);
        }

        // CSV export
        $filename = 'backlinks-' . now()->format('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($opportunities) {
            $file = fopen('php://output', 'w');
            
            // Header row
            fputcsv($file, ['ID', 'URL', 'Type', 'Status', 'Keyword', 'Anchor Text', 'Campaign', 'Domain', 'PA', 'DA', 'Created At', 'Verified At']);
            
            // Data rows
            foreach ($opportunities as $opp) {
                fputcsv($file, [
                    $opp->id,
                    $opp->url ?? $opp->backlink->url ?? '',
                    $opp->type,
                    $opp->status,
                    $opp->keyword ?? '',
                    $opp->anchor_text ?? '',
                    $opp->campaign->name ?? 'N/A',
                    $opp->campaign->domain->name ?? 'N/A',
                    $opp->backlink->pa ?? '',
                    $opp->backlink->da ?? '',
                    $opp->created_at->toDateTimeString(),
                    $opp->verified_at ? $opp->verified_at->toDateTimeString() : '',
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Manual re-check backlink opportunity
     */
    public function recheck($id)
    {
        $campaignIds = Campaign::where('user_id', Auth::id())->pluck('id');
        
        $opportunity = BacklinkOpportunity::whereIn('campaign_id', $campaignIds)
            ->findOrFail($id);

        // Queue verification job (may need to update VerifyBacklinkJob to accept opportunities)
        VerifyBacklinkJob::dispatch($opportunity);

        return back()->with('success', 'Backlink verification queued. Status will update shortly.');
    }

    /**
     * Show backlink opportunities for a campaign
     */
    public function index(Request $request, $campaignId)
    {
        $campaign = Campaign::where('user_id', Auth::id())
            ->with('domain')
            ->findOrFail($campaignId);

        $query = BacklinkOpportunity::where('campaign_id', $campaignId)
            ->with(['backlink:id,url,pa,da,site_type', 'siteAccount'])
            ->latest();

        // Apply filters
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        if ($request->has('search') && $request->search) {
            $query->where(function($q) use ($request) {
                $q->where('url', 'like', '%' . $request->search . '%')
                  ->orWhere('keyword', 'like', '%' . $request->search . '%')
                  ->orWhere('anchor_text', 'like', '%' . $request->search . '%')
                  ->orWhereHas('backlink', function($q) use ($request) {
                      $q->where('url', 'like', '%' . $request->search . '%');
                  });
            });
        }

        $opportunities = $query->paginate(20)->withQueryString();

        // Get stats
        $stats = [
            'total' => BacklinkOpportunity::where('campaign_id', $campaignId)->count(),
            'verified' => BacklinkOpportunity::where('campaign_id', $campaignId)
                ->where('status', BacklinkOpportunity::STATUS_VERIFIED)->count(),
            'pending' => BacklinkOpportunity::where('campaign_id', $campaignId)
                ->where('status', BacklinkOpportunity::STATUS_PENDING)->count(),
            'submitted' => BacklinkOpportunity::where('campaign_id', $campaignId)
                ->where('status', BacklinkOpportunity::STATUS_SUBMITTED)->count(),
            'error' => BacklinkOpportunity::where('campaign_id', $campaignId)
                ->where('status', BacklinkOpportunity::STATUS_FAILED)->count(),
        ];

        return Inertia::render('Campaigns/Backlinks', [
            'campaign' => $campaign,
            'backlinks' => $opportunities,
            'stats' => $stats,
            'filters' => $request->only(['status', 'type', 'search']),
        ]);
    }
}
