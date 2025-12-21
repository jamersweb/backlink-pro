<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Campaign;
use App\Models\Backlink;
use App\Models\AutomationTask;
use App\Models\BacklinkOpportunity;
use App\Models\Plan;
use App\Models\Proxy;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::whereHas('campaigns', function($q) {
                $q->where('status', Campaign::STATUS_ACTIVE);
            })->count(),
            'total_campaigns' => Campaign::count(),
            'active_campaigns' => Campaign::where('status', Campaign::STATUS_ACTIVE)->count(),
            'total_backlinks' => BacklinkOpportunity::count(),
            'verified_backlinks' => BacklinkOpportunity::where('status', BacklinkOpportunity::STATUS_VERIFIED)->count(),
            'pending_tasks' => AutomationTask::where('status', AutomationTask::STATUS_PENDING)->count(),
            'running_tasks' => AutomationTask::where('status', AutomationTask::STATUS_RUNNING)->count(),
            'failed_tasks' => AutomationTask::where('status', AutomationTask::STATUS_FAILED)->count(),
        ];

        $recentCampaigns = Campaign::with('user:id,name')
            ->latest()
            ->limit(10)
            ->get();

        $recentBacklinks = BacklinkOpportunity::with(['campaign:id,name', 'backlink:id,url'])
            ->latest()
            ->limit(10)
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentCampaigns' => $recentCampaigns,
            'recentBacklinks' => $recentBacklinks,
        ]);
    }
}

