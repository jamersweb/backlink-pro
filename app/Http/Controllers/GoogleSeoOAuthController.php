<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\ConnectedAccount;
use App\Models\DomainGoogleIntegration;
use App\Services\Google\GoogleSeoClientFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;

class GoogleSeoOAuthController extends Controller
{
    /**
     * Initiate OAuth connection
     */
    public function connect(Request $request, Domain $domain)
    {
        // Authorize domain ownership
        if ($domain->user_id !== Auth::id()) {
            abort(403);
        }

        try {
            $client = GoogleSeoClientFactory::create();

            // Create signed state with domain context
            $state = [
                'user_id' => Auth::id(),
                'domain_id' => $domain->id,
                'nonce' => bin2hex(random_bytes(16)),
                'ts' => now()->timestamp,
                'return_url' => $request->query('return_url'),
            ];

            $signedState = base64_encode(json_encode($state));
            $signature = hash_hmac('sha256', $signedState, config('app.key'));

            // Store state in session for verification
            session([
                'google_seo_oauth_state' => $signedState,
                'google_seo_oauth_signature' => $signature,
            ]);

            $authUrl = $client->createAuthUrl();
            return redirect($authUrl);
        } catch (\Exception $e) {
            Log::error('Google SEO OAuth connect error', ['error' => $e->getMessage()]);
            return redirect()->route('domains.integrations.google', $domain->id)
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
            return redirect()->route('domains.index')
                ->with('error', 'Google connection failed: ' . $error);
        }

        if (!$code) {
            return redirect()->route('domains.index')
                ->with('error', 'No authorization code received');
        }

        // Verify state
        $signedState = session('google_seo_oauth_state');
        $signature = session('google_seo_oauth_signature');

        if (!$signedState || !$signature) {
            return redirect()->route('domains.index')
                ->with('error', 'Invalid OAuth state');
        }

        // Verify signature
        $expectedSignature = hash_hmac('sha256', $signedState, config('app.key'));
        if (!hash_equals($expectedSignature, $signature)) {
            return redirect()->route('domains.index')
                ->with('error', 'Invalid OAuth signature');
        }

        $state = json_decode(base64_decode($signedState), true);
        $domainId = $state['domain_id'] ?? null;

        if (!$domainId) {
            return redirect()->route('domains.index')
                ->with('error', 'Invalid OAuth state: missing domain');
        }

        $domain = Domain::where('user_id', Auth::id())->findOrFail($domainId);

        try {
            $client = GoogleSeoClientFactory::create();
            $accessToken = $client->fetchAccessTokenWithAuthCode($code);

            if (isset($accessToken['error'])) {
                throw new \Exception('Error fetching access token: ' . $accessToken['error']);
            }

            // Get user profile
            $client->setAccessToken($accessToken['access_token']);
            $oauth2 = new \Google_Service_Oauth2($client);
            $userInfo = $oauth2->userinfo->get();

            // Create or update connected account (SEO service)
            $connectedAccount = ConnectedAccount::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'provider' => ConnectedAccount::PROVIDER_GOOGLE,
                    'email' => $userInfo->getEmail(),
                    'service' => ConnectedAccount::SERVICE_SEO,
                ],
                [
                    'provider_user_id' => $userInfo->getId(),
                    'access_token' => $accessToken['access_token'],
                    'refresh_token' => $accessToken['refresh_token'] ?? null,
                    'expires_at' => now()->addSeconds($accessToken['expires_in'] ?? 3600),
                    'scopes' => [
                        'openid',
                        'email',
                        'profile',
                        'https://www.googleapis.com/auth/webmasters.readonly',
                        'https://www.googleapis.com/auth/analytics.readonly',
                    ],
                    'status' => ConnectedAccount::STATUS_ACTIVE,
                ]
            );

            // Create or update domain integration
            DomainGoogleIntegration::updateOrCreate(
                [
                    'domain_id' => $domain->id,
                ],
                [
                    'user_id' => Auth::id(),
                    'connected_account_id' => $connectedAccount->id,
                    'status' => DomainGoogleIntegration::STATUS_CONNECTED,
                ]
            );

            // Clear session
            $returnUrl = $state['return_url'] ?? null;
            session()->forget(['google_seo_oauth_state', 'google_seo_oauth_signature']);

            // Update onboarding if coming from wizard
            if ($returnUrl && str_contains($returnUrl, '/setup')) {
                $onboarding = \App\Models\DomainOnboarding::where('domain_id', $domain->id)->first();
                if ($onboarding) {
                    $onboarding->markStepDone(\App\Models\DomainOnboarding::STEP_GOOGLE_CONNECTED);
                }
                return redirect($returnUrl)
                    ->with('success', 'Google account connected successfully!');
            }

            return redirect()->route('domains.integrations.google', $domain->id)
                ->with('success', 'Google account connected successfully!');
        } catch (\Exception $e) {
            Log::error('Google SEO OAuth callback error', ['error' => $e->getMessage()]);
            return redirect()->route('domains.integrations.google', $domain->id)
                ->with('error', 'Failed to connect Google: ' . $e->getMessage());
        }
    }
}
