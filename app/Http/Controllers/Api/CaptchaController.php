<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CaptchaSolvingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CaptchaController extends Controller
{
    /**
     * Solve captcha
     */
    public function solve(Request $request)
    {
        // Validate API token
        $apiToken = $request->header('X-API-Token');
        if ($apiToken !== config('app.api_token')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'captcha_type' => 'required|in:recaptcha_v2,recaptcha_v3,hcaptcha,image',
            'data' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $captchaService = new CaptchaSolvingService();
        $result = $captchaService->solve(
            $request->input('captcha_type'),
            $request->input('data'),
            $request->input('campaign_id')
        );

        if ($result) {
            return response()->json([
                'success' => true,
                'solution' => $result['solution'],
                'task_id' => $result['task_id'],
                'cost' => $result['cost'],
            ]);
        } else {
            return response()->json([
                'success' => false,
                'error' => 'Failed to solve captcha',
            ], 500);
        }
    }
}

