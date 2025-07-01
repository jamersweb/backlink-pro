<?php
// app/Http/Controllers/Api/CampaignController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;

class CampaignController extends Controller
{
    /**
     * GET  /api/campaigns
     */
    public function index()
    {
        // return all campaigns as JSON
        return response()->json(
            Campaign::all()
        );
    }

    /**
     * GET  /api/campaigns/{id}
     */
    public function show($id)
    {
        $campaign = Campaign::findOrFail($id);

        return response()->json($campaign);
    }
}
