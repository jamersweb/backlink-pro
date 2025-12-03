<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Backlink;
use App\Jobs\VerifyBacklinkJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BacklinkController extends Controller
{
    /**
     * Show all user's backlinks with filters
     */
    public function all(Request $request)
    {
        $query = Backlink::whereHas('campaign', function($q) {
            $q->where('user_id', Auth::id());
        })
        ->with(['campaign:id,name,domain_id', 'campaign.domain:id,name', 'siteAccount:id,username'])
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
                  });
            });
        }

        $backlinks = $query->paginate(25)->withQueryString();

        // Get statistics
        $stats = [
            'total' => Backlink::whereHas('campaign', function($q) {
                $q->where('user_id', Auth::id());
            })->count(),
            'verified' => Backlink::whereHas('campaign', function($q) {
                $q->where('user_id', Auth::id());
            })->where('status', 'verified')->count(),
            'pending' => Backlink::whereHas('campaign', function($q) {
                $q->where('user_id', Auth::id());
            })->where('status', 'pending')->count(),
            'submitted' => Backlink::whereHas('campaign', function($q) {
                $q->where('user_id', Auth::id());
            })->where('status', 'submitted')->count(),
            'error' => Backlink::whereHas('campaign', function($q) {
                $q->where('user_id', Auth::id());
            })->where('status', 'error')->count(),
        ];

        // Get user's campaigns for filter dropdown
        $campaigns = Campaign::where('user_id', Auth::id())
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('Backlinks/Index', [
            'backlinks' => $backlinks,
            'stats' => $stats,
            'campaigns' => $campaigns,
            'filters' => $request->only(['campaign_id', 'status', 'type', 'date_from', 'date_to', 'search']),
        ]);
    }

    /**
     * Export backlinks
     */
    public function export(Request $request)
    {
        $query = Backlink::whereHas('campaign', function($q) {
            $q->where('user_id', Auth::id());
        })
        ->with(['campaign:id,name', 'campaign.domain:id,name']);

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

        $backlinks = $query->get();
        $format = $request->get('format', 'csv'); // csv or json

        if ($format === 'json') {
            return response()->json($backlinks->map(function($backlink) {
                return [
                    'id' => $backlink->id,
                    'url' => $backlink->url,
                    'type' => $backlink->type,
                    'status' => $backlink->status,
                    'keyword' => $backlink->keyword,
                    'anchor_text' => $backlink->anchor_text,
                    'campaign' => $backlink->campaign->name ?? 'N/A',
                    'domain' => $backlink->campaign->domain->name ?? 'N/A',
                    'created_at' => $backlink->created_at->toISOString(),
                    'verified_at' => $backlink->verified_at ? $backlink->verified_at->toISOString() : null,
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

        $callback = function() use ($backlinks) {
            $file = fopen('php://output', 'w');
            
            // Header row
            fputcsv($file, ['ID', 'URL', 'Type', 'Status', 'Keyword', 'Anchor Text', 'Campaign', 'Domain', 'Created At', 'Verified At']);
            
            // Data rows
            foreach ($backlinks as $backlink) {
                fputcsv($file, [
                    $backlink->id,
                    $backlink->url,
                    $backlink->type,
                    $backlink->status,
                    $backlink->keyword,
                    $backlink->anchor_text,
                    $backlink->campaign->name ?? 'N/A',
                    $backlink->campaign->domain->name ?? 'N/A',
                    $backlink->created_at->toDateTimeString(),
                    $backlink->verified_at ? $backlink->verified_at->toDateTimeString() : '',
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Manual re-check backlink
     */
    public function recheck($id)
    {
        $backlink = Backlink::whereHas('campaign', function($q) {
            $q->where('user_id', Auth::id());
        })->findOrFail($id);

        // Queue verification job
        VerifyBacklinkJob::dispatch($backlink);

        return back()->with('success', 'Backlink verification queued. Status will update shortly.');
    }

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

