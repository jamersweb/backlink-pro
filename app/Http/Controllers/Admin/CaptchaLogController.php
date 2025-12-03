<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CaptchaLog;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CaptchaLogController extends Controller
{
    public function index(Request $request)
    {
        $query = CaptchaLog::with(['campaign:id,name,user_id', 'campaign.user:id,name,email'])
            ->latest();

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->has('captcha_type') && $request->captcha_type) {
            $query->where('captcha_type', $request->captcha_type);
        }

        // Filter by service
        if ($request->has('service') && $request->service) {
            $query->where('service', $request->service);
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
                $q->where('site_domain', 'like', "%{$search}%")
                  ->orWhere('order_id', 'like', "%{$search}%")
                  ->orWhere('error', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate(50)->withQueryString();

        // Get stats
        $totalCost = CaptchaLog::where('status', CaptchaLog::STATUS_SOLVED)->sum('estimated_cost');
        $todayCost = CaptchaLog::where('status', CaptchaLog::STATUS_SOLVED)
            ->whereDate('created_at', today())
            ->sum('estimated_cost');
        $thisWeekCost = CaptchaLog::where('status', CaptchaLog::STATUS_SOLVED)
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('estimated_cost');
        $thisMonthCost = CaptchaLog::where('status', CaptchaLog::STATUS_SOLVED)
            ->whereMonth('created_at', now()->month)
            ->sum('estimated_cost');

        $stats = [
            'total' => CaptchaLog::count(),
            'solved' => CaptchaLog::where('status', CaptchaLog::STATUS_SOLVED)->count(),
            'failed' => CaptchaLog::where('status', CaptchaLog::STATUS_FAILED)->count(),
            'pending' => CaptchaLog::where('status', CaptchaLog::STATUS_PENDING)->count(),
            'total_cost' => $totalCost,
            'today_cost' => $todayCost,
            'this_week_cost' => $thisWeekCost,
            'this_month_cost' => $thisMonthCost,
        ];

        // Get filter options
        $campaigns = Campaign::select('id', 'name', 'user_id')
            ->with('user:id,name')
            ->orderBy('name')
            ->get();
        
        $users = User::select('id', 'name', 'email')->orderBy('name')->get();

        return Inertia::render('Admin/CaptchaLogs/Index', [
            'logs' => $logs,
            'stats' => $stats,
            'campaigns' => $campaigns,
            'users' => $users,
            'filters' => $request->only(['status', 'captcha_type', 'service', 'campaign_id', 'user_id', 'date_from', 'date_to', 'search']),
        ]);
    }
}

