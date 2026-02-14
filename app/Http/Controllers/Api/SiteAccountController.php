<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteAccount;
use App\Models\Campaign;
use App\Jobs\WaitForVerificationEmailJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SiteAccountController extends Controller
{
    /**
     * Create site account
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
            'site_domain' => 'required|string|max:255',
            'login_email' => 'required|email',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string',
            'status' => 'nullable|in:created,waiting_email,verified,failed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $campaign = Campaign::findOrFail($data['campaign_id']);
        if (empty($data['user_id'])) {
            $data['user_id'] = $campaign->user_id;
        }

        $siteAccount = SiteAccount::create($data);

        if ($campaign->requires_email_verification && $campaign->gmailAccount) {
            $siteAccount->update([
                'status' => SiteAccount::STATUS_WAITING_EMAIL,
                'email_verification_status' => SiteAccount::EMAIL_STATUS_PENDING,
                'last_verification_check_at' => now(),
            ]);

            WaitForVerificationEmailJob::dispatch($siteAccount, $campaign->id);
        }

        return response()->json([
            'message' => 'Site account created successfully',
            'id' => $siteAccount->id,
            'site_account' => $siteAccount,
        ], 201);
    }

    /**
     * Update site account
     */
    public function update(Request $request, $id)
    {
        // Validate API token
        $apiToken = $request->header('X-API-Token');
        if ($apiToken !== config('app.api_token')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $siteAccount = SiteAccount::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:created,waiting_email,verified,failed',
            'verification_link' => 'nullable|url',
            'email_verification_status' => 'nullable|in:pending,found,timeout',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $siteAccount->update($validator->validated());

        return response()->json([
            'message' => 'Site account updated successfully',
            'site_account' => $siteAccount,
        ]);
    }
}

