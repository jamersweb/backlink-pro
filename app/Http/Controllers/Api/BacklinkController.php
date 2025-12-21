<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BacklinkOpportunity;
use App\Models\Backlink;
use App\Models\Campaign;
use App\Services\RateLimitingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BacklinkController extends Controller
{
    /**
     * Create a backlink opportunity (campaign-specific)
     * This is called by Python worker when a backlink is created
     */
    public function store(Request $request)
    {
        // Validate API token
        $apiToken = $request->header('X-API-Token');
        $expectedToken = trim(config('app.api_token', ''));
        
        // Handle token migration
        $validTokens = [$expectedToken];
        if ($expectedToken === 'your-api-token-here') {
            $validTokens[] = 'your-secure-api-token-change-in-production';
        } elseif ($expectedToken === 'your-secure-api-token-change-in-production') {
            $validTokens[] = 'your-api-token-here';
        }
        
        if (empty($apiToken) || !in_array($apiToken, $validTokens)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'campaign_id' => 'required|exists:campaigns,id',
            'backlink_id' => 'required|exists:backlinks,id', // Reference to backlinks store
            'url' => 'nullable|url', // Actual backlink URL (may differ from store URL)
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

        // Get the backlink from store
        $backlink = Backlink::findOrFail($request->backlink_id);
        
        // Use actual URL or fallback to store URL
        $actualUrl = $request->url ?? $backlink->url;

        // Check domain rate limit (max 1 backlink per domain per day per campaign)
        if (!RateLimitingService::checkDomainRateLimit($actualUrl, $request->campaign_id)) {
            return response()->json([
                'error' => 'Rate limit exceeded: Maximum 1 backlink per domain per day for this campaign',
                'message' => 'This domain has already received a backlink today for this campaign. Please try again tomorrow.',
            ], 429);
        }

        // Create opportunity (campaign-specific)
        $opportunity = BacklinkOpportunity::create([
            'campaign_id' => $request->campaign_id,
            'backlink_id' => $request->backlink_id,
            'url' => $actualUrl,
            'type' => $request->type,
            'keyword' => $request->keyword,
            'anchor_text' => $request->anchor_text,
            'status' => $request->status,
            'site_account_id' => $request->site_account_id,
            'error_message' => $request->error_message,
            'verified_at' => $request->status === 'verified' ? now() : null,
        ]);

        return response()->json([
            'message' => 'Backlink opportunity created successfully',
            'opportunity' => $opportunity->load('backlink'),
        ], 201);
    }

    /**
     * Update backlink opportunity
     */
    public function update(Request $request, $id)
    {
        // Validate API token
        $apiToken = $request->header('X-API-Token');
        $expectedToken = trim(config('app.api_token', ''));
        
        $validTokens = [$expectedToken];
        if ($expectedToken === 'your-api-token-here') {
            $validTokens[] = 'your-secure-api-token-change-in-production';
        } elseif ($expectedToken === 'your-secure-api-token-change-in-production') {
            $validTokens[] = 'your-api-token-here';
        }
        
        if (empty($apiToken) || !in_array($apiToken, $validTokens)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $opportunity = BacklinkOpportunity::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:pending,submitted,verified,error',
            'error_message' => 'nullable|string',
            'verified_at' => 'nullable|date',
            'url' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $updateData = $validator->validated();

        if ($request->status === 'verified' && !$opportunity->verified_at) {
            $updateData['verified_at'] = now();
        }

        $opportunity->update($updateData);

        return response()->json([
            'message' => 'Backlink opportunity updated successfully',
            'opportunity' => $opportunity->load('backlink'),
        ]);
    }
}
