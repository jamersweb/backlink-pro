<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Backlink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BacklinkController extends Controller
{
    /**
     * Show backlinks for a campaign
     */
    public function index(Request $request, $campaignId)
    {
        $campaign = Campaign::where('user_id', Auth::id())
            ->with('domain')
            ->findOrFail($campaignId);

        $query = Backlink::where('campaign_id', $campaignId)
            ->with('siteAccount')
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
                  ->orWhere('anchor_text', 'like', '%' . $request->search . '%');
            });
        }

        $backlinks = $query->paginate(20)->withQueryString();

        // Get stats
        $stats = [
            'total' => Backlink::where('campaign_id', $campaignId)->count(),
            'verified' => Backlink::where('campaign_id', $campaignId)->where('status', 'verified')->count(),
            'pending' => Backlink::where('campaign_id', $campaignId)->where('status', 'pending')->count(),
            'submitted' => Backlink::where('campaign_id', $campaignId)->where('status', 'submitted')->count(),
            'error' => Backlink::where('campaign_id', $campaignId)->where('status', 'error')->count(),
        ];

        return Inertia::render('Campaigns/Backlinks', [
            'campaign' => $campaign,
            'backlinks' => $backlinks,
            'stats' => $stats,
            'filters' => $request->only(['status', 'type', 'search']),
        ]);
    }
}

