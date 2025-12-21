<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\Domain;
use App\Models\ConnectedAccount;
use App\Models\Backlink;
use App\Models\AutomationTask;
use App\Services\ActivityLogService;
use App\Services\NotificationService;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreUserCampaignRequest;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class UserCampaignController extends Controller
{
public function index()
{
    $campaigns = Campaign::where('user_id', Auth::id())
                         ->withCount('backlinks')
                         ->latest()
                         ->get();
    return Inertia::render('Campaigns/Index', [
        'campaigns' => $campaigns,
    ]);
}

public function show($id)
{
  $campaign = Campaign::with(['country','state','city','domain','gmailAccount'])
                  ->where('user_id', Auth::id())
                  ->findOrFail($id);
    return Inertia::render('Campaigns/Show', [
        'campaign' => $campaign,
    ]);
}

    public function create()
{
    $user = Auth::user();
    $plan = $user->plan;
    
    // Get plan settings or defaults
    $planSettings = [
        'daily_limit' => $plan ? $plan->daily_backlink_limit : 10,
        'total_limit' => $plan ? ($plan->daily_backlink_limit * 30) : 300, // Monthly limit based on daily
        'backlink_types' => $plan ? ($plan->backlink_types ?? []) : ['comment', 'profile'],
    ];
    
    return Inertia::render('Campaigns/Create', [
        'countries' => Country::all(['id','name']) ?? [],
        'states'    => State::all(['id','name','country_id']) ?? [],
        'cities'    => City::all(['id','name','state_id']) ?? [],
        'domains' => Domain::where('user_id', Auth::id())->get(['id', 'name']) ?? [],
        'connectedAccounts' => ConnectedAccount::where('user_id', Auth::id())->get(['id', 'email']) ?? [],
        'planSettings' => $planSettings,
        'plan' => $plan ? [
            'name' => $plan->name,
            'daily_backlink_limit' => $plan->daily_backlink_limit,
            'backlink_types' => $plan->backlink_types ?? [],
        ] : null,
    ]);
}

 public function edit($id)
    {
        // Ensure the campaign belongs to the authenticated user
        $campaign = Campaign::where('user_id', Auth::id())
                            ->findOrFail($id);

        return Inertia::render('Campaigns/Edit', [
            'campaign'  => $campaign,
            'countries' => Country::all(['id', 'name']),
            'states'    => State::all(['id', 'name', 'country_id']),
            'cities'    => City::all(['id', 'name', 'state_id']),
            'domains' => Domain::where('user_id', Auth::id())->get(['id', 'name']),
            'connectedAccounts' => ConnectedAccount::where('user_id', Auth::id())->get(['id', 'email']),
        ]);
    }
    public function store(StoreUserCampaignRequest $request)
{
    $data = $request->validated();
    $data['user_id'] = Auth::id();
    
    // Set default name if not provided
    if (empty($data['name'])) {
        $data['name'] = $data['web_name'] ?? 'Untitled Campaign';
    }

    if ($request->hasFile('company_logo')) {
        $file     = $request->file('company_logo');
        $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
        $destinationPath = public_path('images/company_logo');
        // ensure directory exists
        if (! file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }
        $file->move($destinationPath, $filename);
        // save path relative to public/
        $data['company_logo'] = 'images/company_logo/' . $filename;
    }

    // If gmail_account_id is provided, use it and remove gmail/password
    if (!empty($data['gmail_account_id'])) {
        unset($data['gmail']);
        unset($data['password']);
    }

    // Get user's plan settings
    $user = Auth::user();
    $plan = $user->plan;
    
    // Check plan limits before creating campaign
    if ($plan) {
        // Check max campaigns limit (-1 means unlimited)
        if ($plan->max_campaigns !== -1) {
            $userCampaignCount = Campaign::where('user_id', $user->id)->count();
            if ($userCampaignCount >= $plan->max_campaigns) {
                return back()->withErrors([
                    'campaign_limit' => "You have reached your plan's maximum campaign limit ({$plan->max_campaigns}). Please upgrade your plan or delete an existing campaign to create a new one."
                ])->withInput();
            }
        }
        
        // Check if user has a plan (required for creating campaigns)
        if (!$plan) {
            return back()->withErrors([
                'plan' => 'You need an active plan to create campaigns. Please subscribe to a plan first.'
            ])->withInput();
        }
    } else {
        return back()->withErrors([
            'plan' => 'You need an active plan to create campaigns. Please subscribe to a plan first.'
        ])->withInput();
    }
    
    // Use plan settings automatically
    $planDailyLimit = $plan ? $plan->daily_backlink_limit : 10;
    $planBacklinkTypes = $plan ? ($plan->backlink_types ?? []) : ['comment', 'profile'];
    
    // Prepare settings JSON - use plan settings, not user input
    $settings = [
        'backlink_types' => $planBacklinkTypes, // From plan
        'daily_limit' => $planDailyLimit, // From plan
        'total_limit' => $data['total_limit'] ?? ($planDailyLimit * 30), // Monthly limit based on daily
        'content_tone' => $data['content_tone'] ?? 'professional',
        'anchor_text_strategy' => $data['anchor_text_strategy'] ?? 'variation',
    ];
    $data['settings'] = $settings;
    $data['daily_limit'] = $planDailyLimit; // Also set directly for campaign
    $data['total_limit'] = $data['total_limit'] ?? ($planDailyLimit * 30);
    $data['status'] = Campaign::STATUS_ACTIVE;

    $campaign = Campaign::create($data);
    
    // Create initial automation tasks based on plan
    $this->createInitialTasks($campaign, $plan, $settings);
    
    // Log activity
    ActivityLogService::logCampaignCreated($campaign);
    
    // Send notification
    NotificationService::notifyCampaignCreated($campaign);

    return redirect()
        ->route('user-campaign.index')
        ->with('success', 'Campaign created successfully. Initial tasks have been queued.');
}

public function update(StoreUserCampaignRequest $request, $id)
{
    $campaign = Campaign::where('user_id', Auth::id())->findOrFail($id);
    $data     = $request->validated();

    // Handle company logo - only update if a new file is uploaded
    if ($request->hasFile('company_logo')) {
        // delete old file
        if ($campaign->company_logo && file_exists(public_path($campaign->company_logo))) {
            unlink(public_path($campaign->company_logo));
        }
        $file     = $request->file('company_logo');
        $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
        $destinationPath = public_path('images/company_logo');
        if (! file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }
        $file->move($destinationPath, $filename);
        $data['company_logo'] = 'images/company_logo/' . $filename;
    } else {
        // Remove company_logo from update data if no new file is uploaded
        // Keep the existing logo in the database
        unset($data['company_logo']);
    }

    // Convert country/state/city to integers
    if (isset($data['company_country'])) {
        $data['company_country'] = (int) $data['company_country'];
    }
    if (isset($data['company_state'])) {
        $data['company_state'] = (int) $data['company_state'];
    }
    if (isset($data['company_city']) && !empty($data['company_city'])) {
        $data['company_city'] = (int) $data['company_city'];
    } else {
        $data['company_city'] = null;
    }

    // Handle Gmail account
    if (!empty($data['gmail_account_id'])) {
        $data['gmail_account_id'] = (int) $data['gmail_account_id'];
        unset($data['gmail']);
        unset($data['password']);
    } else {
        $data['gmail_account_id'] = null;
    }

    // Get user's plan settings and update campaign settings automatically
    $user = Auth::user();
    $plan = $user->plan;
    
    if ($plan) {
        $planDailyLimit = $plan->daily_backlink_limit;
        $planBacklinkTypes = $plan->backlink_types ?? [];
        
        // Update settings from plan
        $settings = is_array($campaign->settings) ? $campaign->settings : (json_decode($campaign->settings, true) ?? []);
        $settings['backlink_types'] = $planBacklinkTypes;
        $settings['daily_limit'] = $planDailyLimit;
        $settings['total_limit'] = $settings['total_limit'] ?? ($planDailyLimit * 30);
        $settings['content_tone'] = $settings['content_tone'] ?? 'professional';
        $settings['anchor_text_strategy'] = $settings['anchor_text_strategy'] ?? 'variation';
        
        $data['settings'] = $settings;
        $data['daily_limit'] = $planDailyLimit;
        $data['total_limit'] = $data['total_limit'] ?? ($planDailyLimit * 30);
    }

    // Remove settings fields from data if they were submitted
    unset($data['backlink_types']);
    
    // Remove company_logo if it's null (keep existing logo)
    if (isset($data['company_logo']) && $data['company_logo'] === null) {
        unset($data['company_logo']);
    }

    $oldStatus = $campaign->status;
    $oldStartDate = $campaign->start_date;
    
    $campaign->update($data);
    $campaign->refresh();

    // Create tasks if campaign becomes active or start_date is set/updated
    $shouldCreateTasks = false;
    
    if ($campaign->status === Campaign::STATUS_ACTIVE) {
        // Check if start_date is now or in the past (or not set)
        $startDatePassed = !$campaign->start_date || 
                          ($campaign->start_date instanceof \Carbon\Carbon ? $campaign->start_date->lte(now()) : strtotime($campaign->start_date) <= time());
        
        if ($startDatePassed) {
            // Check if we have existing tasks
            $existingTasksCount = AutomationTask::where('campaign_id', $campaign->id)->count();
            
            // Create tasks if:
            // 1. Campaign just became active (status changed from inactive/paused to active)
            // 2. Start date was just set/updated and is now or in the past
            // 3. No tasks exist yet
            $statusChanged = $oldStatus !== Campaign::STATUS_ACTIVE;
            $startDateChanged = $oldStartDate != $campaign->start_date;
            
            if (($statusChanged || $startDateChanged) && $existingTasksCount === 0) {
                $shouldCreateTasks = true;
            }
        }
    }

    if ($shouldCreateTasks) {
        $this->createInitialTasks($campaign, $plan, $settings);
    }
    
    // Log activity
    ActivityLogService::logCampaignUpdated($campaign);

    return redirect()
        ->route('user-campaign.index')
        ->with('success', 'Campaign updated successfully.' . ($shouldCreateTasks ? ' Tasks have been queued.' : ''));
}

public function destroy($id)
{
    $campaign = Campaign::where('user_id', Auth::id())->findOrFail($id);
    
    // Log activity before deletion
    ActivityLogService::logCampaignDeleted($campaign);
    
    // Delete company logo file if exists
    if ($campaign->company_logo && file_exists(public_path($campaign->company_logo))) {
        unlink(public_path($campaign->company_logo));
    }
    
    $campaign->delete();

    return redirect()
        ->route('user-campaign.index')
        ->with('success', 'Campaign deleted successfully.');
}

/**
 * Pause a campaign
 */
public function pause($id)
{
    $campaign = Campaign::where('user_id', Auth::id())->findOrFail($id);
    
    if ($campaign->status === Campaign::STATUS_PAUSED) {
        return back()->with('info', 'Campaign is already paused.');
    }
    
    $campaign->update(['status' => Campaign::STATUS_PAUSED]);
    
    // Log activity
    ActivityLogService::logCampaignPaused($campaign);
    
    // Send notification
    NotificationService::notifyCampaignPaused($campaign);
    
    return back()->with('success', 'Campaign paused successfully.');
}

/**
 * Resume a campaign
 */
public function resume($id)
{
    $campaign = Campaign::where('user_id', Auth::id())->findOrFail($id);
    
    if ($campaign->status === Campaign::STATUS_ACTIVE) {
        return back()->with('info', 'Campaign is already active.');
    }
    
    // Check if campaign has reached its total limit
    $campaignTotalBacklinks = $campaign->backlinks()->count();
    if ($campaignTotalBacklinks >= ($campaign->total_limit ?? 100)) {
        return back()->with('error', 'Cannot resume campaign: Total limit reached.');
    }
    
    $campaign->update(['status' => Campaign::STATUS_ACTIVE]);
    
    // Log activity
    ActivityLogService::logCampaignResumed($campaign);
    
    // Send notification
    NotificationService::notifyCampaignResumed($campaign);
    
    return back()->with('success', 'Campaign resumed successfully.');
}

/**
 * Export campaigns data
 */
public function export(Request $request)
{
    $query = Campaign::where('user_id', Auth::id())
        ->with(['domain:id,name', 'country:id,name', 'state:id,name', 'city:id,name'])
        ->withCount('backlinks');

    $campaigns = $query->get();

    $format = $request->get('format', 'csv');

    if ($format === 'json') {
        $data = $campaigns->map(function($campaign) {
            return [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'status' => $campaign->status,
                'domain' => $campaign->domain->name ?? 'N/A',
                'country' => $campaign->country->name ?? 'N/A',
                'state' => $campaign->state->name ?? 'N/A',
                'city' => $campaign->city->name ?? 'N/A',
                'total_backlinks' => $campaign->backlinks_count,
                'daily_limit' => $campaign->daily_limit,
                'total_limit' => $campaign->total_limit,
                'web_url' => $campaign->web_url,
                'created_at' => $campaign->created_at->toISOString(),
            ];
        });

        return ExportService::exportJson(
            $data->toArray(),
            'campaigns-' . now()->format('Y-m-d') . '.json'
        );
    }

    // CSV export
    $headers = ['ID', 'Name', 'Status', 'Domain', 'Country', 'State', 'City', 'Total Backlinks', 'Daily Limit', 'Total Limit', 'Web URL', 'Created At'];
    $data = $campaigns->map(function($campaign) {
        return [
            $campaign->id,
            $campaign->name,
            $campaign->status,
            $campaign->domain->name ?? 'N/A',
            $campaign->country->name ?? 'N/A',
            $campaign->state->name ?? 'N/A',
            $campaign->city->name ?? 'N/A',
            $campaign->backlinks_count,
            $campaign->daily_limit ?? 'N/A',
            $campaign->total_limit ?? 'N/A',
            $campaign->web_url ?? 'N/A',
            $campaign->created_at->toDateTimeString(),
        ];
    })->toArray();

    return ExportService::exportCsv(
        $data,
        $headers,
        'campaigns-' . now()->format('Y-m-d') . '.csv'
    );
}

    /**
     * Create initial automation tasks for a new campaign based on plan
     */
    protected function createInitialTasks(Campaign $campaign, $plan, array $settings): void
    {
        if (!$plan) {
            return;
        }

        // Get backlink types from plan
        $backlinkTypes = $plan->backlink_types ?? ['comment', 'profile'];
        
        if (empty($backlinkTypes)) {
            return; // No backlink types allowed
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

        // Create tasks for each backlink type
        foreach ($backlinkTypes as $type) {
            // Check if plan allows this backlink type
            if (!$plan->allowsBacklinkType($type)) {
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
            }
        }
    }

}
