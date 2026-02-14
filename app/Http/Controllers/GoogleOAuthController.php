<?php

namespace App\Http\Controllers;

use App\Models\ConnectedAccount;
use App\Services\Google\GoogleSeoClientFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GoogleOAuthController extends Controller
{
    /**
     * Initiate OAuth flow for Google SEO (GA4 + GSC)
     */
    public function connect(Request $request)
    {
        try {
            $client = GoogleSeoClientFactory::create();

            // Create signed state
            $state = [
                'user_id' => Auth::id(),
                'nonce' => bin2hex(random_bytes(16)),
                'ts' => now()->timestamp,
                'return_url' => '/audit-report',
            ];

            $signedState = base64_encode(json_encode($state));
            $signature = hash_hmac('sha256', $signedState, config('app.key'));

            // Store in session
            session([
                'google_seo_oauth_state' => $signedState,
                'google_seo_oauth_signature' => $signature,
            ]);

            $authUrl = $client->createAuthUrl();
            return redirect($authUrl);
        } catch (\Exception $e) {
            Log::error('Google OAuth connect error', ['error' => $e->getMessage()]);
            return redirect()->route('audit-report.index')
                ->with('error', 'Failed to connect Google: ' . $e->getMessage());
        }
    }

    /**
     * Handle OAuth callback
     */
    public function callback(Request $request)
    {
        $code = $request->get('code');
        $error = $request->get('error');

        if ($error) {
            return redirect()->route('audit-report.index')
                ->with('error', 'Google connection failed: ' . $error);
        }

        if (!$code) {
            return redirect()->route('audit-report.index')
                ->with('error', 'No authorization code received');
        }

        // Verify state
        $signedState = session('google_seo_oauth_state');
        $signature = session('google_seo_oauth_signature');

        if (!$signedState || !$signature) {
            return redirect()->route('audit-report.index')
                ->with('error', 'Invalid OAuth state');
        }

        $expectedSignature = hash_hmac('sha256', $signedState, config('app.key'));
        if (!hash_equals($expectedSignature, $signature)) {
            return redirect()->route('audit-report.index')
                ->with('error', 'Invalid OAuth signature');
        }

        $state = json_decode(base64_decode($signedState), true);

        try {
            $client = GoogleSeoClientFactory::create();
            $client->fetchAccessTokenWithAuthCode($code);
            $accessToken = $client->getAccessToken();
            $refreshToken = $client->getRefreshToken();

            if (!$accessToken || !$refreshToken) {
                throw new \Exception('Failed to obtain access token');
            }

            // Get user info
            $oauth2 = new \Google_Service_Oauth2($client);
            $userInfo = $oauth2->userinfo->get();

            // Store or update connected account
            ConnectedAccount::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'provider' => 'google',
                    'service' => 'seo',
                ],
                [
                    'email' => $userInfo->email,
                    'provider_user_id' => $userInfo->id,
                    'access_token' => $accessToken['access_token'],
                    'refresh_token' => $refreshToken,
                    'expires_at' => isset($accessToken['expires_in']) 
                        ? now()->addSeconds($accessToken['expires_in']) 
                        : null,
                    'scopes' => $accessToken['scope'] ?? null,
                    'status' => 'active',
                ]
            );

            session()->forget(['google_seo_oauth_state', 'google_seo_oauth_signature']);

            return redirect()->route('audit-report.index')
                ->with('success', 'Successfully connected Google Analytics and Search Console!');
        } catch (\Exception $e) {
            Log::error('Google OAuth callback error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return redirect()->route('audit-report.index')
                ->with('error', 'Failed to complete Google connection: ' . $e->getMessage());
        }
    }

    /**
     * Disconnect Google account
     */
    public function disconnect(Request $request)
    {
        ConnectedAccount::where('user_id', Auth::id())
            ->where('provider', 'google')
            ->where('service', 'seo')
            ->delete();

        return redirect()->route('audit-report.index')
            ->with('success', 'Google account disconnected successfully');
    }

    /**
     * Get connection status
     */
    public function status()
    {
        $account = ConnectedAccount::where('user_id', Auth::id())
            ->where('provider', 'google')
            ->where('service', 'seo')
            ->where('status', 'active')
            ->first();

        return response()->json([
            'connected' => (bool) $account,
            'email' => $account?->email,
        ]);
    }
}
