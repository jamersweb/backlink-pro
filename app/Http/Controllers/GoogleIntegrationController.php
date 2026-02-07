<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\OauthConnection;
use App\Models\GscSite;
use App\Models\Ga4Property;
use App\Services\SEO\GoogleClient;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GoogleIntegrationController extends Controller
{
    /**
     * Show Google integration page
     */
    public function index(Organization $organization)
    {
        $this->authorize('view', $organization);

        $connection = OauthConnection::where('organization_id', $organization->id)
            ->where('provider', 'google')
            ->first();

        $gscSites = GscSite::where('organization_id', $organization->id)->get();
        $ga4Properties = Ga4Property::where('organization_id', $organization->id)->get();

        return Inertia::render('SEO/Integrations', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'connection' => $connection ? [
                'id' => $connection->id,
                'account_email' => $connection->account_email,
                'status' => $connection->status,
                'last_error' => $connection->last_error,
            ] : null,
            'gscSites' => $gscSites->map(function ($site) {
                return [
                    'id' => $site->id,
                    'site_url' => $site->site_url,
                    'permission_level' => $site->permission_level,
                    'is_active' => $site->is_active ?? false,
                ];
            }),
            'ga4Properties' => $ga4Properties->map(function ($property) {
                return [
                    'id' => $property->id,
                    'property_id' => $property->property_id,
                    'display_name' => $property->display_name,
                    'is_active' => $property->is_active ?? false,
                ];
            }),
        ]);
    }

    /**
     * Initiate Google OAuth connection
     */
    public function connect(Organization $organization)
    {
        $this->authorize('manage', $organization);

        $clientId = config('services.google.client_id');
        $redirectUri = route('integrations.google.callback', ['organization' => $organization->id]);
        $scopes = [
            'https://www.googleapis.com/auth/webmasters.readonly',
            'https://www.googleapis.com/auth/analytics.readonly',
        ];

        $state = Str::random(40);
        session(['google_oauth_state' => $state, 'google_oauth_org' => $organization->id]);

        $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', $scopes),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ]);

        return redirect($authUrl);
    }

    /**
     * Handle Google OAuth callback
     */
    public function callback(Request $request, Organization $organization)
    {
        $this->authorize('manage', $organization);

        $state = $request->query('state');
        $code = $request->query('code');

        if ($state !== session('google_oauth_state') || !$code) {
            return redirect()->route('integrations.google', ['organization' => $organization->id])
                ->with('error', 'Invalid OAuth response.');
        }

        try {
            // Exchange code for tokens
            $response = \Illuminate\Support\Facades\Http::post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => route('integrations.google.callback', ['organization' => $organization->id]),
            ]);

            if (!$response->successful()) {
                throw new \Exception("Token exchange failed: " . $response->body());
            }

            $data = $response->json();

            // Get user info
            $userResponse = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => "Bearer {$data['access_token']}",
            ])->get('https://www.googleapis.com/oauth2/v2/userinfo');

            $userData = $userResponse->json();

            // Create or update connection
            $connection = OauthConnection::updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'provider' => 'google',
                ],
                [
                    'account_email' => $userData['email'] ?? null,
                    'access_token' => $data['access_token'],
                    'refresh_token' => $data['refresh_token'] ?? null,
                    'expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
                    'scopes' => explode(' ', $data['scope'] ?? ''),
                    'status' => OauthConnection::STATUS_ACTIVE,
                ]
            );

            // Fetch available sites and properties
            $client = new GoogleClient($connection);
            $this->fetchAvailableResources($organization, $client);

            return redirect()->route('integrations.google', ['organization' => $organization->id])
                ->with('success', 'Google account connected successfully.');

        } catch (\Exception $e) {
            Log::error("Google OAuth callback failed", [
                'organization_id' => $organization->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('integrations.google', ['organization' => $organization->id])
                ->with('error', 'Failed to connect Google account: ' . $e->getMessage());
        }
    }

    /**
     * Fetch available GSC sites and GA4 properties
     */
    protected function fetchAvailableResources(Organization $organization, GoogleClient $client): void
    {
        try {
            // Fetch GSC sites
            $sites = $client->fetchGscSites();
            foreach ($sites as $site) {
                GscSite::updateOrCreate(
                    [
                        'organization_id' => $organization->id,
                        'site_url' => $site['siteUrl'],
                    ],
                    [
                        'permission_level' => $site['permissionLevel'] ?? null,
                    ]
                );
            }

            // Fetch GA4 properties
            $properties = $client->fetchGa4Properties();
            foreach ($properties as $property) {
                Ga4Property::updateOrCreate(
                    [
                        'organization_id' => $organization->id,
                        'property_id' => $property['name'] ?? null,
                    ],
                    [
                        'display_name' => $property['displayName'] ?? 'Unknown',
                    ]
                );
            }

        } catch (\Exception $e) {
            Log::warning("Failed to fetch Google resources", [
                'organization_id' => $organization->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Disconnect Google account
     */
    public function disconnect(Organization $organization)
    {
        $this->authorize('manage', $organization);

        $connection = OauthConnection::where('organization_id', $organization->id)
            ->where('provider', 'google')
            ->first();

        if ($connection) {
            $connection->update(['status' => OauthConnection::STATUS_REVOKED]);
        }

        return redirect()->back()->with('success', 'Google account disconnected.');
    }

    /**
     * Select active GSC site
     */
    public function selectGscSite(Request $request, Organization $organization)
    {
        $this->authorize('manage', $organization);

        $validated = $request->validate([
            'site_url' => ['required', 'string'],
        ]);

        // Mark as active
        GscSite::where('organization_id', $organization->id)
            ->update(['is_active' => false]);

        $site = GscSite::where('organization_id', $organization->id)
            ->where('site_url', $validated['site_url'])
            ->first();

        if ($site) {
            $site->update(['is_active' => true]);
        }

        return redirect()->back()->with('success', 'GSC site selected.');
    }

    /**
     * Select active GA4 property
     */
    public function selectGa4Property(Request $request, Organization $organization)
    {
        $this->authorize('manage', $organization);

        $validated = $request->validate([
            'property_id' => ['required', 'string'],
        ]);

        // Mark as active
        Ga4Property::where('organization_id', $organization->id)
            ->update(['is_active' => false]);

        $property = Ga4Property::where('organization_id', $organization->id)
            ->where('property_id', $validated['property_id'])
            ->first();

        if ($property) {
            $property->update(['is_active' => true]);
        }

        return redirect()->back()->with('success', 'GA4 property selected.');
    }
}
