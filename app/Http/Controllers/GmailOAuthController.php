<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\GmailService;
use App\Models\ConnectedAccount;
use Inertia\Inertia;

class GmailOAuthController extends Controller
{
    /**
     * Redirect to Google OAuth
     */
    public function connect()
    {
        try {
            // Check if Google credentials are configured
            if (empty(config('services.google.client_id')) || empty(config('services.google.client_secret'))) {
                return redirect()->route('gmail.index')
                    ->with('error', 'Google OAuth is not configured. Please add GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET to your .env file. See the documentation for setup instructions.');
            }

            $gmailService = new GmailService();
            $authUrl = $gmailService->getAuthUrl();
            
            // Store state in session for CSRF protection
            session(['gmail_oauth_state' => bin2hex(random_bytes(16))]);
            
            return redirect($authUrl);
        } catch (\Exception $e) {
            \Log::error('Gmail OAuth connect error: ' . $e->getMessage());
            return redirect()->route('gmail.index')
                ->with('error', 'Failed to connect Gmail: ' . $e->getMessage());
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
            return redirect()->route('dashboard')
                ->with('error', 'Gmail connection failed: ' . $error);
        }

        if (!$code) {
            return redirect()->route('dashboard')
                ->with('error', 'No authorization code received');
        }

        try {
            $gmailService = new GmailService();
            $tokens = $gmailService->exchangeCodeForTokens($code);
            
            // Get user profile
            $gmailService->setAccessToken($tokens['access_token']);
            $profile = $gmailService->getUserProfile();

            // Create or update connected account
            $connectedAccount = ConnectedAccount::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'provider' => ConnectedAccount::PROVIDER_GOOGLE,
                    'email' => $profile['email'],
                ],
                [
                    'provider_user_id' => $profile['id'],
                    'access_token' => $tokens['access_token'],
                    'refresh_token' => $tokens['refresh_token'],
                    'expires_at' => $tokens['expires_at'],
                    'scopes' => ['openid', 'email', 'profile', 'https://www.googleapis.com/auth/gmail.readonly'],
                    'status' => ConnectedAccount::STATUS_ACTIVE,
                ]
            );

            return redirect()->route('dashboard')
                ->with('success', 'Gmail account connected successfully!');
        } catch (\Exception $e) {
            return redirect()->route('dashboard')
                ->with('error', 'Failed to connect Gmail: ' . $e->getMessage());
        }
    }

    /**
     * Show Gmail accounts management page
     */
    public function index()
    {
        try {
            $connectedAccounts = ConnectedAccount::where('user_id', Auth::id())
                ->where('provider', ConnectedAccount::PROVIDER_GOOGLE)
                ->withCount('campaigns')
                ->latest()
                ->get();

            return Inertia::render('Gmail/Index', [
                'connectedAccounts' => $connectedAccounts,
            ]);
        } catch (\Exception $e) {
            \Log::error('Gmail index error: ' . $e->getMessage());
            return Inertia::render('Gmail/Index', [
                'connectedAccounts' => collect([]),
                'error' => 'Failed to load Gmail accounts. Please try again.',
            ]);
        }
    }

    /**
     * Disconnect Gmail account
     */
    public function disconnect($id)
    {
        $connectedAccount = ConnectedAccount::where('user_id', Auth::id())
            ->findOrFail($id);

        $connectedAccount->update([
            'status' => ConnectedAccount::STATUS_REVOKED,
        ]);

        return redirect()->back()
            ->with('success', 'Gmail account disconnected successfully');
    }
}

