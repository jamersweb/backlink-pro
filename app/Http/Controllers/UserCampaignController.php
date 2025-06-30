<?php

namespace App\Http\Controllers;

use App\Models\WebInformation;
use App\Models\CompanyInformation;
use App\Models\UserGmail;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserCampaignController extends Controller
{
    /**
     * Show the form.
     */
    public function create()
    {
        // load countries for the web-country dropdown
        $countries = Country::all();
        return view('user.campaign.user-campaign', compact('countries'));
    }

    /**
     * Handle submission.
     */
   public function store(Request $request)
{
    $data = $request->validate([
        // WebInformation
        'web_name'      => 'required|string|max:255',
        'web_url'       => 'required|url',
        'web_keyword'   => 'required|string|max:255',
        'web_about'     => 'required|string',
        'web_target'    => 'required|in:worldwide,specific_country',
        'country_name'  => 'required_if:web_target,specific_country|string|max:255',

        // CompanyInformation
        'company_name'           => 'required|string|max:255',
        'company_logo'           => 'required|image|mimes:jpg,jpeg,png,gif|max:2048',
        'company_email_address'  => 'required|email',
        'company_address'        => 'required|string',
        'company_number'         => 'required|string|max:50',
        'company_country'        => 'required|string|max:255',
        'company_city'           => 'required|string|max:255',
        'company_state'          => 'required|string|max:255',

        // UserGmail
        'gmail'    => 'required|email',
        'password' => 'required|string',
    ]);

    DB::transaction(function() use ($data, $request) {
        // 1) WebInformation
        $web = WebInformation::create([
            'user_id'      => Auth::id(),
            'web_name'     => $data['web_name'],
            'web_url'      => $data['web_url'],
            'web_keyword'  => $data['web_keyword'],
            'web_about'    => $data['web_about'],
            'web_target'   => $data['web_target'],
            'country_name' => $data['country_name'] ?? null,
        ]);

        // 2) CompanyInformation
        $logoPath = $request->file('company_logo')
                            ->store('company_logos', 'public');

        $company = CompanyInformation::create([
            'user_id'               => Auth::id(),
            'company_name'          => $data['company_name'],
            'company_logo'          => $logoPath,
            'company_email_address' => $data['company_email_address'],
            'company_address'       => $data['company_address'],
            'company_number'        => $data['company_number'],
            'company_country'       => $data['company_country'],
            'company_city'          => $data['company_city'],
            'company_state'         => $data['company_state'],
        ]);

        // 3) UserGmail (no company_id or web_id)
        UserGmail::create([
            'user_id'  => Auth::id(),
            'gmail'    => $data['gmail'],
            'password' => $data['password'],
        ]);
    });

    return redirect()
           ->route('user-campaign.create')
           ->with('success', 'Campaign, Company & Gmail saved successfully.');
}

}
