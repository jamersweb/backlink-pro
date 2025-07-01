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

    public function create()
{
    // remove any dd() here…
    return view('user.campaign.user-campaign', [
        'countries' => Country::all(['id','name']),
        'states'    => State::all(['id','name','country_id']),
        'cities'    => City::all(['id','name','state_id']),
    ]);
}


  public function store(StoreUserCampaignRequest $request)
    {
        // validated data
        $data = $request->validated();

        // attach the user
        $data['user_id'] = Auth::id();

        // handle the logo upload (optional)
        if ($request->hasFile('company_logo')) {
            $data['company_logo'] = $request
                ->file('company_logo')
                ->store('company_logos','public');
        }

        // persist
        Campaign::create($data);

        // redirect back to index with a success message
        return redirect()
            ->route('user-campaign.index')
            ->with('success', 'Campaign created successfully.');
    }

}
