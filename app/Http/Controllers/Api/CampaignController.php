<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/campaigns
     * List all active campaigns with basic filtering
     */
    public function index(Request $request)
    {
        $query = Campaign::with(['user:id,name,email', 'category:id,name', 'domain:id,domain']);

        // Optional filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Only return active campaigns by default
        if (!$request->has('include_inactive')) {
            $query->where('status', Campaign::STATUS_ACTIVE);
        }

        $campaigns = $query->orderBy('created_at', 'desc')->get();

        return $this->success($campaigns, 'Campaigns retrieved successfully');
    }

    /**
     * GET /api/campaigns/{id}
     * Get a single campaign with full details
     */
    public function show($id)
    {
        $campaign = Campaign::with([
            'user:id,name,email',
            'domain:id,domain',
            'category:id,name',
            'subcategory:id,name',
            'gmailAccount:id,email',
        ])->find($id);

        if (!$campaign) {
            return $this->notFound('Campaign not found');
        }

        return $this->success($campaign, 'Campaign retrieved successfully');
    }
}
