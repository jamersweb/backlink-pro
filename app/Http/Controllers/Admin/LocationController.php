<?php

namespace App\Http\Controllers\Admin;

use App\Models\Country;
use App\Models\State;
use App\Models\City;
use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

class LocationController extends Controller
{
    // 1) Form load karte waqt countries bhejne ke liye
    public function create()
    {
        $countries = Country::all();
        return view('admin.locations.form', compact('countries'));
    }

    // 2) AJAX se states load karne ke liye
    public function getStates($country)
    {
        $states = State::where('country_id', $country)->get();
        return response()->json($states);
    }

    // 3) AJAX se cities load karne ke liye
    public function getCities($state)
    {
        $cities = City::where('state_id', $state)->get();
        return response()->json($cities);
    }
}
