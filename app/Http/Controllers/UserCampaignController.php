<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\Domain;
use App\Models\ConnectedAccount;
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

    Campaign::create($data);

    return redirect()
        ->route('user-campaign.index')
        ->with('success', 'Campaign created successfully.');
}

public function update(StoreUserCampaignRequest $request, $id)
{
    $campaign = Campaign::where('user_id', Auth::id())->findOrFail($id);
    $data     = $request->validated();

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
    }

    $campaign->update($data);

    return redirect()
        ->route('user-campaign.index')
        ->with('success', 'Campaign updated successfully.');
}

public function destroy($id)
{
    $campaign = Campaign::where('user_id', Auth::id())->findOrFail($id);
    
    // Delete company logo file if exists
    if ($campaign->company_logo && file_exists(public_path($campaign->company_logo))) {
        unlink(public_path($campaign->company_logo));
    }
    
    $campaign->delete();

    return redirect()
        ->route('user-campaign.index')
        ->with('success', 'Campaign deleted successfully.');
}

}
