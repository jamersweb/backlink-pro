<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AutomationTask;
use App\Models\Campaign;
use App\Models\User;
use App\Models\BacklinkOpportunity;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AutomationTaskController extends Controller
{
    public function index(Request $request)
    {
        $query = AutomationTask::with(['campaign:id,name,user_id', 'campaign.user:id,name,email'])
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
                $q->where('error_message', 'like', "%{$search}%")
                  ->orWhere('locked_by', 'like', "%{$search}%");
            });
        }

        $tasks = $query->paginate(50)->withQueryString();

        // Get stats
        $stats = [
            'total' => AutomationTask::count(),
            'pending' => AutomationTask::where('status', AutomationTask::STATUS_PENDING)->count(),
            'running' => AutomationTask::where('status', AutomationTask::STATUS_RUNNING)->count(),
            'success' => AutomationTask::where('status', AutomationTask::STATUS_SUCCESS)->count(),
            'failed' => AutomationTask::where('status', AutomationTask::STATUS_FAILED)->count(),
            'cancelled' => AutomationTask::where('status', AutomationTask::STATUS_CANCELLED)->count(),
            'today' => AutomationTask::whereDate('created_at', today())->count(),
            'this_week' => AutomationTask::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
        ];

        // Get filter options
        $campaigns = Campaign::select('id', 'name', 'user_id')
            ->with('user:id,name')
            ->orderBy('name')
            ->get();
        
        $users = User::select('id', 'name', 'email')->orderBy('name')->get();

        return Inertia::render('Admin/AutomationTasks/Index', [
            'tasks' => $tasks,
            'stats' => $stats,
            'campaigns' => $campaigns,
            'users' => $users,
            'filters' => $request->only(['status', 'type', 'campaign_id', 'user_id', 'date_from', 'date_to', 'search']),
        ]);
    }

    public function retry(AutomationTask $task)
    {
        if ($task->status !== AutomationTask::STATUS_FAILED) {
            return back()->with('error', 'Only failed tasks can be retried.');
        }

        $task->update([
            'status' => AutomationTask::STATUS_PENDING,
            'retry_count' => 0,
            'error_message' => null,
            'locked_at' => null,
            'locked_by' => null,
        ]);

        return back()->with('success', 'Task queued for retry.');
    }

    public function cancel(AutomationTask $task)
    {
        if (!in_array($task->status, [AutomationTask::STATUS_PENDING, AutomationTask::STATUS_RUNNING])) {
            return back()->with('error', 'Only pending or running tasks can be cancelled.');
        }

        $task->update([
            'status' => AutomationTask::STATUS_CANCELLED,
            'locked_at' => null,
            'locked_by' => null,
        ]);

        return back()->with('success', 'Task cancelled successfully.');
    }

    public function show(AutomationTask $task)
    {
        $task->load([
            'campaign:id,name,user_id,web_url,web_keyword,category_id,subcategory_id',
            'campaign.user:id,name,email',
            'campaign.category:id,name',
            'campaign.subcategory:id,name',
        ]);

        // Get related backlink opportunities created by this task
        $backlinks = BacklinkOpportunity::where('campaign_id', $task->campaign_id)
            ->where('type', $task->type)
            ->whereDate('created_at', $task->created_at->toDateString())
            ->latest()
            ->limit(10)
            ->get();

        return Inertia::render('Admin/AutomationTasks/Show', [
            'task' => $task,
            'backlinks' => $backlinks,
        ]);
    }
}

