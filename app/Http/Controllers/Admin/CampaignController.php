<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function index()
    {
        $campaigns = Campaign::orderBy('created_at', 'desc')->paginate(10);
        return view('admin.campaign.index', compact('campaigns'));
    }

    public function create()
    {
        return view('admin.campaign.form');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            // add other fields and validation rules as needed
        ]);

        Campaign::create($data);
        return redirect()->route('admin.campaigns.index')
                         ->with('success', 'Campaign created successfully.');
    }

    public function show(Campaign $campaign)
    {
        return view('admin.campaign.show', compact('campaign'));
    }

    public function edit(Campaign $campaign)
    {
        return view('admin.campaign.form', compact('campaign'));
    }

    public function update(Request $request, Campaign $campaign)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            // add other fields and validation rules as needed
        ]);

        $campaign->update($data);
        return redirect()->route('admin.campaigns.index')
                         ->with('success', 'Campaign updated successfully.');
    }

    public function destroy(Campaign $campaign)
    {
        $campaign->delete();
        return response()->json(['status' => 'success']);
    }
}