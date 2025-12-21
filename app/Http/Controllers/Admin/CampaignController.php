<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\User;
use App\Models\Domain;
use App\Models\AutomationTask;
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
            'category_id' => 'nullable|exists:categories,id',
            'subcategory_id' => 'nullable|exists:categories,id',
        ]);

        $oldStatus = $campaign->status;
        $oldStartDate = $campaign->start_date;
        
        $campaign->update($validated);
        $campaign->refresh();

        // Create tasks if campaign becomes active or start_date is set/updated
        $shouldCreateTasks = false;
        $reason = '';
        $message = 'Campaign updated successfully.';
        
        if ($campaign->status === Campaign::STATUS_ACTIVE) {
            // Check if start_date is now or in the past (or not set)
            $startDatePassed = !$campaign->start_date;
            if ($campaign->start_date) {
                if ($campaign->start_date instanceof \Carbon\Carbon) {
                    $startDatePassed = $campaign->start_date->lte(now());
                } else {
                    $startDatePassed = strtotime($campaign->start_date) <= time();
                }
            }
            
            if ($startDatePassed) {
                // Check if we have existing tasks
                $existingTasksCount = AutomationTask::where('campaign_id', $campaign->id)->count();
                
                // Create tasks if:
                // 1. Campaign just became active (status changed from inactive/paused to active)
                // 2. Start date was just set/updated and is now or in the past
                // 3. No tasks exist yet OR we're updating start_date
                $statusChanged = $oldStatus !== Campaign::STATUS_ACTIVE;
                $startDateChanged = false;
                if ($oldStartDate && $campaign->start_date) {
                    $oldDate = $oldStartDate instanceof \Carbon\Carbon ? $oldStartDate->format('Y-m-d H:i:s') : $oldStartDate;
                    $newDate = $campaign->start_date instanceof \Carbon\Carbon ? $campaign->start_date->format('Y-m-d H:i:s') : $campaign->start_date;
                    $startDateChanged = $oldDate != $newDate;
                } elseif (!$oldStartDate && $campaign->start_date) {
                    $startDateChanged = true;
                }
                
                // Create tasks if:
                // 1. Status changed to active (and no tasks exist)
                // 2. Start date changed (always create new tasks when start date is updated)
                if ($statusChanged && $existingTasksCount === 0) {
                    $shouldCreateTasks = true;
                    $reason = 'Status changed to active';
                } elseif ($startDateChanged) {
                    $shouldCreateTasks = true;
                    $reason = 'Start date updated';
                } else {
                    $reason = $existingTasksCount > 0 ? "Tasks already exist ({$existingTasksCount})" : 'No status or date change detected';
                }
            } else {
                $reason = 'Start date is in the future';
            }
        } else {
            $reason = 'Campaign status is not active';
        }

        if ($shouldCreateTasks) {
            try {
                $tasksCreated = $this->createTasksForCampaign($campaign);
                $message = 'Campaign updated successfully. Tasks have been queued.';
            } catch (\Exception $e) {
                \Log::error('Failed to create tasks for campaign', [
                    'campaign_id' => $campaign->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $message = 'Campaign updated successfully, but failed to create tasks: ' . $e->getMessage();
            }
        } else {
            if ($reason) {
                $message .= " ({$reason})";
            }
        }

        return redirect()->route('admin.campaigns.show', $campaign)
            ->with('success', $message);
    }

    /**
     * Create tasks for a campaign based on user's plan
     */
    protected function createTasksForCampaign(Campaign $campaign): int
    {
        $campaign->load('user.plan');
        $user = $campaign->user;
        $plan = $user ? $user->plan : null;

        if (!$plan) {
            \Log::warning('Cannot create tasks: User has no plan', ['campaign_id' => $campaign->id, 'user_id' => $user?->id]);
            return 0;
        }

        // Get backlink types from plan
        $backlinkTypes = $plan->backlink_types ?? ['comment', 'profile'];
        
        if (empty($backlinkTypes)) {
            \Log::warning('Cannot create tasks: Plan has no backlink types', ['campaign_id' => $campaign->id, 'plan_id' => $plan->id]);
            return 0;
        }
        
        // Calculate initial tasks per type (based on daily limit)
        $dailyLimit = $plan->daily_backlink_limit ?? 10;
        $tasksPerType = max(1, floor($dailyLimit / count($backlinkTypes)));

        // Handle keywords - convert string to array if needed
        $keywords = $campaign->web_keyword ?? '';
        if (is_string($keywords)) {
            $keywords = !empty($keywords) ? explode(',', $keywords) : [];
            $keywords = array_map('trim', $keywords);
            $keywords = array_filter($keywords);
        }
        if (empty($keywords)) {
            $keywords = [$campaign->web_name ?? 'SEO'];
        }

        $settings = $campaign->settings ?? [];
        if (!is_array($settings)) {
            $settings = json_decode($settings, true) ?? [];
        }

        $totalCreated = 0;

        // Create tasks for each backlink type
        foreach ($backlinkTypes as $type) {
            // Check if plan allows this backlink type
            if (!$plan->allowsBacklinkType($type)) {
                \Log::info('Skipping backlink type not allowed by plan', [
                    'campaign_id' => $campaign->id,
                    'type' => $type,
                    'plan_id' => $plan->id,
                ]);
                continue;
            }

            // Map 'guestposting' to 'guest' (task type enum uses 'guest')
            $taskType = $type === 'guestposting' ? 'guest' : $type;

            // Create initial batch of tasks
            for ($i = 0; $i < $tasksPerType; $i++) {
                AutomationTask::create([
                    'campaign_id' => $campaign->id,
                    'type' => $taskType,
                    'status' => AutomationTask::STATUS_PENDING,
                    'payload' => [
                        'campaign_id' => $campaign->id,
                        'keywords' => $keywords,
                        'anchor_text_strategy' => $settings['anchor_text_strategy'] ?? 'variation',
                        'content_tone' => $settings['content_tone'] ?? 'professional',
                    ],
                    'max_retries' => 3,
                    'retry_count' => 0,
                ]);
                $totalCreated++;
            }
        }

        \Log::info('Tasks created for campaign', [
            'campaign_id' => $campaign->id,
            'tasks_created' => $totalCreated,
            'backlink_types' => $backlinkTypes,
            'tasks_per_type' => $tasksPerType,
        ]);

        return $totalCreated;
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

    /**
     * Manually create tasks for a campaign
     */
    public function createTasks(Campaign $campaign)
    {
        try {
            $tasksCreated = $this->createTasksForCampaign($campaign);
            
            if ($tasksCreated > 0) {
                return back()->with('success', "Successfully created {$tasksCreated} task(s) for this campaign.");
            } else {
                return back()->with('error', 'No tasks were created. Please check if the user has a plan with backlink types configured.');
            }
        } catch (\Exception $e) {
            \Log::error('Failed to manually create tasks for campaign', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);
            
            return back()->with('error', 'Failed to create tasks: ' . $e->getMessage());
        }
    }
}