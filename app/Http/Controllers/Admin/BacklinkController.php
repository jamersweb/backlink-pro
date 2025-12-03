<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Backlink;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BacklinkController extends Controller
{
    public function index(Request $request)
    {
        $query = Backlink::with(['campaign:id,name,user_id', 'campaign.user:id,name,email', 'siteAccount:id,site_domain'])
            ->latest();

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

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
                  ->orWhere('anchor_text', 'like', "%{$search}%");
            });
        }

        $backlinks = $query->paginate(50)->withQueryString();

        // Get stats
        $stats = [
            'total' => Backlink::count(),
            'verified' => Backlink::where('status', 'verified')->count(),
            'pending' => Backlink::where('status', 'pending')->count(),
            'submitted' => Backlink::where('status', 'submitted')->count(),
            'error' => Backlink::where('status', 'error')->count(),
            'today' => Backlink::whereDate('created_at', today())->count(),
            'this_week' => Backlink::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'this_month' => Backlink::whereMonth('created_at', now()->month)->count(),
        ];

        // Get filter options
        $campaigns = Campaign::select('id', 'name', 'user_id')
            ->with('user:id,name')
            ->orderBy('name')
            ->get();
        
        $users = User::select('id', 'name', 'email')->orderBy('name')->get();

        return Inertia::render('Admin/Backlinks/Index', [
            'backlinks' => $backlinks,
            'stats' => $stats,
            'campaigns' => $campaigns,
            'users' => $users,
            'filters' => $request->only(['status', 'type', 'campaign_id', 'user_id', 'date_from', 'date_to', 'search']),
        ]);
    }

    public function export(Request $request)
    {
        $query = Backlink::with(['campaign:id,name', 'campaign.user:id,name,email']);

        // Apply same filters as index
        if ($request->has('status') && $request->status) {
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

        $backlinks = $query->get();

        $filename = 'backlinks_export_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($backlinks) {
            $file = fopen('php://output', 'w');
            
            // Header row
            fputcsv($file, [
                'ID',
                'Campaign',
                'User',
                'URL',
                'Type',
                'Keyword',
                'Anchor Text',
                'Status',
                'Verified At',
                'Error Message',
                'Created At',
            ]);

            // Data rows
            foreach ($backlinks as $backlink) {
                fputcsv($file, [
                    $backlink->id,
                    $backlink->campaign->name ?? 'N/A',
                    $backlink->campaign->user->name ?? 'N/A',
                    $backlink->url,
                    $backlink->type,
                    $backlink->keyword,
                    $backlink->anchor_text,
                    $backlink->status,
                    $backlink->verified_at ? $backlink->verified_at->format('Y-m-d H:i:s') : '',
                    $backlink->error_message,
                    $backlink->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

