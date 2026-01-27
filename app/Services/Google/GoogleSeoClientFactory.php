<?php

namespace App\Services\Google;

use Google_Client;
use App\Models\ConnectedAccount;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class GoogleSeoClientFactory
{
    /**
     * Create Google Client for SEO (GSC + GA4)
     */
    public static function create(?ConnectedAccount $connectedAccount = null): Google_Client
    {
        // Read credentials from Setting first, fallback to config
        $clientId = Setting::get('google_client_id') ?: config('services.google.client_id');
        $clientSecret = Setting::get('google_client_secret') ?: config('services.google.client_secret');
        $redirectUri = Setting::get('google_seo_redirect_uri') ?: config('services.google_seo.redirect_uri');

        if (empty($clientId) || empty($clientSecret)) {
            throw new \Exception('Google OAuth credentials are not configured. Please configure them in Admin Settings.');
        }

        $client = new Google_Client();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($redirectUri);
        $client->setScopes([
            'openid',
            'email',
            'profile',
            'https://www.googleapis.com/auth/webmasters.readonly',
            'https://www.googleapis.com/auth/analytics.readonly',
        ]);
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        if ($connectedAccount) {
            $client->setAccessToken($connectedAccount->access_token);
            if ($connectedAccount->refresh_token) {
                $client->setRefreshToken($connectedAccount->refresh_token);
            }

            // Refresh token if expiring soon
            if ($connectedAccount->isExpiredOrExpiringSoon()) {
                self::refreshToken($connectedAccount, $client);
            }
        }

        return $client;
    }

    /**
     * Refresh access token and update ConnectedAccount
     */
    protected static function refreshToken(ConnectedAccount $account, Google_Client $client): void
    {
        if (!$account->refresh_token) {
            throw new \Exception('No refresh token available');
        }

        try {
            $client->refreshToken($account->refresh_token);
            $accessToken = $client->getAccessToken();

            if (isset($accessToken['error'])) {
                throw new \Exception('Error refreshing token: ' . $accessToken['error']);
            }

            // Update connected account
            $account->update([
                'access_token' => $accessToken['access_token'],
                'refresh_token' => $accessToken['refresh_token'] ?? $account->refresh_token,
                'expires_at' => now()->addSeconds($accessToken['expires_in'] ?? 3600),
            ]);
        } catch (\Exception $e) {
            Log::error('Google SEO token refresh failed', [
                'error' => $e->getMessage(),
                'account_id' => $account->id,
            ]);
            
            $account->update(['status' => ConnectedAccount::STATUS_EXPIRED]);
            throw $e;
        }
    }
}


