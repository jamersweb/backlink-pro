<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Backlink;
use App\Models\Campaign;
use App\Services\RateLimitingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BacklinkController extends Controller
{
    /**
     * Create or update backlink
     */
    public function store(Request $request)
    {
        // Validate API token
        $apiToken = $request->header('X-API-Token');
        if ($apiToken !== config('app.api_token')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'campaign_id' => 'required|exists:campaigns,id',
            'url' => 'required|url',
            'type' => 'required|in:comment,profile,forum,guestposting',
            'keyword' => 'nullable|string',
            'anchor_text' => 'nullable|string',
            'status' => 'required|in:pending,submitted,verified,error',
            'site_account_id' => 'nullable|exists:site_accounts,id',
            'error_message' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check domain rate limit (max 1 backlink per domain per day per campaign)
        if (!RateLimitingService::checkDomainRateLimit($request->url, $request->campaign_id)) {
            return response()->json([
                'error' => 'Rate limit exceeded: Maximum 1 backlink per domain per day for this campaign',
                'message' => 'This domain has already received a backlink today for this campaign. Please try again tomorrow.',
            ], 429);
        }

        $backlink = Backlink::create([
            'campaign_id' => $request->campaign_id,
            'url' => $request->url,
            'type' => $request->type,
            'keyword' => $request->keyword,
            'anchor_text' => $request->anchor_text,
            'status' => $request->status,
            'site_account_id' => $request->site_account_id,
            'error_message' => $request->error_message,
            'verified_at' => $request->status === 'verified' ? now() : null,
        ]);

        return response()->json([
            'message' => 'Backlink created successfully',
            'backlink' => $backlink,
        ], 201);
    }

    /**
     * Update backlink
     */
    public function update(Request $request, $id)
    {
        // Validate API token
        $apiToken = $request->header('X-API-Token');
        if ($apiToken !== config('app.api_token')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $backlink = Backlink::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:pending,submitted,verified,error',
            'error_message' => 'nullable|string',
            'verified_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $updateData = $validator->validated();

        if ($request->status === 'verified' && !$backlink->verified_at) {
            $updateData['verified_at'] = now();
        }

        $backlink->update($updateData);

        return response()->json([
            'message' => 'Backlink updated successfully',
            'backlink' => $backlink,
        ]);
    }
}

