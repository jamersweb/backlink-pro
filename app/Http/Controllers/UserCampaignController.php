<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreUserCampaignRequest;
use Illuminate\Support\Facades\Storage;

class UserCampaignController extends Controller
{
public function index()
{
    $campaigns = Campaign::where('user_id', Auth::id())
                         ->latest()
                         ->get();
    return view('user.campaign.index', compact('campaigns'));
}

public function show($id)
{
  $campaign = Campaign::with(['country','state','city'])
                  ->where('user_id', Auth::id())
                  ->findOrFail($id);
    return view('user.campaign.campaign-views', compact('campaign'));
}

    public function create()
{
    // remove any dd() here…
    return view('user.campaign.user-campaign', [
        'countries' => Country::all(['id','name']),
        'states'    => State::all(['id','name','country_id']),
        'cities'    => City::all(['id','name','state_id']),
    ]);
}

 public function edit($id)
    {
        // Ensure the campaign belongs to the authenticated user
        $campaign = Campaign::where('user_id', Auth::id())
                            ->findOrFail($id);

        return view('user.campaign.user-campaign', [
            'campaign'  => $campaign,
            'countries' => Country::all(['id', 'name']),
            'states'    => State::all(['id', 'name', 'country_id']),
            'cities'    => City::all(['id', 'name', 'state_id']),
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


}
