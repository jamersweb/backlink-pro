<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GoogleGa4Controller extends Controller
{
    /**
     * Redirect to Google OAuth for GA4
     */
    public function redirect(Request $request)
    {
        $user = $request->user();

        $clientId = config('services.google.client_id');
        $redirectUri = config('services.google.redirect_uri');

        if (!$clientId || !$redirectUri) {
            return redirect()->back()->with('error', 'Google OAuth is not configured.');
        }

        $state = Str::random(40);
        $returnUrl = $request->query('return_url');
        if (!$returnUrl || !str_starts_with($returnUrl, '/')) {
            $returnUrl = '/audit';
        }

        session([
            'ga4_oauth_state' => $state,
            'ga4_oauth_user_id' => $user->id,
            'ga4_oauth_return_url' => $returnUrl,
        ]);

        $scopes = [
            'openid',
            'email',
            'profile',
            'https://www.googleapis.com/auth/analytics.readonly',
        ];

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
     * Handle OAuth callback
     */
    public function callback(Request $request)
    {
        $user = $request->user();
        $error = $request->query('error');
        $code = $request->query('code');
        $state = $request->query('state');

        $returnUrl = session('ga4_oauth_return_url', '/audit');
        if (!str_starts_with($returnUrl, '/')) {
            $returnUrl = '/audit';
        }

        if ($error) {
            $message = $error === 'redirect_uri_mismatch'
                ? 'Redirect URI mismatch. Please ensure GOOGLE_REDIRECT_URI matches the callback URL in Google Cloud Console.'
                : 'Google connection failed: ' . $error;

            return redirect($returnUrl)->with('error', $message);
        }

        if (!$code || !$state || $state !== session('ga4_oauth_state')) {
            return redirect($returnUrl)->with('error', 'Invalid OAuth response.');
        }

        if ((int) session('ga4_oauth_user_id') !== (int) $user->id) {
            return redirect($returnUrl)->with('error', 'OAuth session mismatch.');
        }

        try {
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => config('services.google.redirect_uri'),
            ]);

            if (!$response->successful()) {
                $body = $response->body();
                if (str_contains($body, 'redirect_uri_mismatch')) {
                    throw new \Exception('Redirect URI mismatch. Please ensure GOOGLE_REDIRECT_URI matches the callback URL in Google Cloud Console.');
                }
                throw new \Exception('Token exchange failed: ' . $body);
            }

            $data = $response->json();

            $userInfo = Http::withHeaders([
                'Authorization' => "Bearer {$data['access_token']}",
            ])->get('https://www.googleapis.com/oauth2/v2/userinfo');

            $userData = $userInfo->json();

            $user->google_provider = 'google';
            $user->google_access_token = $data['access_token'];
            if (!empty($data['refresh_token'])) {
                $user->google_refresh_token = $data['refresh_token'];
            }
            $user->google_token_expires_at = now()->addSeconds($data['expires_in'] ?? 3600);
            $user->google_connected_at = now();
            if (!empty($userData['email'])) {
                $user->google_email = $userData['email'];
            }
            $user->save();

            session()->forget(['ga4_oauth_state', 'ga4_oauth_user_id', 'ga4_oauth_return_url']);

            return redirect($returnUrl)->with('success', 'Google Analytics (GA4) connected successfully.');
        } catch (\Exception $e) {
            Log::error('GA4 OAuth callback failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return redirect($returnUrl)->with('error', $e->getMessage());
        }
    }

    /**
     * Disconnect GA4
     */
    public function disconnect(Request $request)
    {
        $user = $request->user();

        $user->google_provider = null;
        $user->google_access_token = null;
        $user->google_refresh_token = null;
        $user->google_token_expires_at = null;
        $user->google_connected_at = null;
        $user->google_email = null;
        $user->ga4_property_id = null;
        $user->save();

        return redirect()->back()->with('success', 'Google Analytics (GA4) disconnected.');
    }

    /**
     * List GA4 properties for the connected user
     */
    public function properties(Request $request)
    {
        $user = $request->user();
        $domain = $request->query('domain');

        try {
            $token = $this->getAccessToken($user);
        } catch (\Exception $e) {
            return response()->json([
                'properties' => [],
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ], 400);
        }

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->get('https://analyticsadmin.googleapis.com/v1beta/accountSummaries');

        if (!$response->successful()) {
            $body = $response->body();
            $message = 'Failed to fetch GA4 properties.';
            if (str_starts_with(ltrim($body), '<!DOCTYPE html>')) {
                $message .= ' Google Analytics Admin API endpoint returned HTML. Please ensure the Analytics Admin API is enabled in Google Cloud Console.';
            } else {
                $message .= ' ' . $body;
            }

            return response()->json([
                'properties' => [],
                'error' => $message,
            ], 400);
        }

        $properties = collect($response->json()['accountSummaries'] ?? [])
            ->flatMap(fn($account) => $account['propertySummaries'] ?? [])
            ->map(fn($property) => [
                'property_id' => $property['property'] ?? null,
                'display_name' => $property['displayName'] ?? 'Unknown',
            ])
            ->filter(fn($property) => !empty($property['property_id']))
            ->unique('property_id')
            ->values()
            ->all();

        if (empty($properties)) {
            return response()->json([
                'properties' => [],
                'message' => "You don't have access to any GA4 properties. Please add access in GA4 Admin.",
            ]);
        }

        $selected = $user->ga4_property_id;
        if (!$selected) {
            if (count($properties) === 1) {
                $selected = $properties[0]['property_id'];
            } elseif ($domain) {
                $selected = $this->matchPropertyByDomain($token, $properties, $domain);
            }

            if ($selected) {
                $user->ga4_property_id = $selected;
                $user->save();
            }
        }

        return response()->json([
            'properties' => $properties,
            'selected_property_id' => $selected,
        ]);
    }

    /**
     * Fetch GA4 summary for the selected property
     */
    public function summary(Request $request)
    {
        $user = $request->user();

        $propertyId = $request->query('property_id') ?: $user->ga4_property_id;
        if (!$propertyId) {
            return response()->json([
                'error' => 'No GA4 property selected.',
            ], 400);
        }

        $propertyId = $this->normalizePropertyId($propertyId);
        if ($request->query('property_id')) {
            $user->ga4_property_id = $propertyId;
            $user->save();
        }

        try {
            $token = $this->getAccessToken($user);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ], 400);
        }

        $endDate = now()->toDateString();
        $startDate = now()->subDays(28)->toDateString();

        try {
            $summary = $this->runReport($token, $propertyId, [
                'dateRanges' => [
                    ['startDate' => $startDate, 'endDate' => $endDate],
                ],
                'metrics' => [
                    ['name' => 'activeUsers'],
                    ['name' => 'sessions'],
                    ['name' => 'engagementRate'],
                    ['name' => 'averageSessionDuration'],
                    ['name' => 'conversions'],
                ],
            ]);

            $topPages = $this->runReport($token, $propertyId, [
                'dateRanges' => [
                    ['startDate' => $startDate, 'endDate' => $endDate],
                ],
                'dimensions' => [
                    ['name' => 'pagePath'],
                ],
                'metrics' => [
                    ['name' => 'sessions'],
                    ['name' => 'activeUsers'],
                ],
                'orderBys' => [
                    ['metric' => ['metricName' => 'sessions'], 'desc' => true],
                ],
                'limit' => 10,
            ]);

            $topSources = $this->runReportWithSourceFallback($token, $propertyId, $startDate, $endDate);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }

        $current = $this->formatSummary($summary);

        $sourceDimension = collect($topSources['dimensionHeaders'] ?? [])
            ->pluck('name')
            ->contains('sourceMedium') ? 'sourceMedium' : 'sessionSourceMedium';

        return response()->json([
            'property_id' => $propertyId,
            'range' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'current' => $current,
            'top_pages' => $this->formatRows($topPages, ['pagePath'], ['sessions', 'activeUsers'], [
                'page_path' => 'pagePath',
                'sessions' => 'sessions',
                'active_users' => 'activeUsers',
            ]),
            'top_sources' => $this->formatRows($topSources, [$sourceDimension], ['sessions', 'activeUsers'], [
                'source_medium' => $sourceDimension,
                'sessions' => 'sessions',
                'active_users' => 'activeUsers',
            ]),
        ]);
    }

    protected function getAccessToken($user): string
    {
        if (!$user->google_access_token && !$user->google_refresh_token) {
            throw new \Exception('Please connect Google to continue.', 401);
        }

        $expiresAt = $user->google_token_expires_at;
        $needsRefresh = !$user->google_access_token
            || !$expiresAt
            || $expiresAt->isPast()
            || $expiresAt->copy()->subMinutes(2)->isPast();

        if ($needsRefresh) {
            if (!$user->google_refresh_token) {
                throw new \Exception('Please disconnect and reconnect Google to grant offline access.', 412);
            }

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'refresh_token' => $user->google_refresh_token,
                'grant_type' => 'refresh_token',
            ]);

            if (!$response->successful()) {
                throw new \Exception('Failed to refresh Google access token: ' . $response->body(), 401);
            }

            $data = $response->json();
            $user->google_access_token = $data['access_token'];
            $user->google_token_expires_at = now()->addSeconds($data['expires_in'] ?? 3600);
            if (!empty($data['refresh_token'])) {
                $user->google_refresh_token = $data['refresh_token'];
            }
            $user->save();
        }

        return $user->google_access_token;
    }

    protected function normalizePropertyId(string $propertyId): string
    {
        $propertyId = trim($propertyId);
        if ($propertyId === '') {
            return $propertyId;
        }

        if (str_starts_with($propertyId, 'properties/')) {
            return $propertyId;
        }

        return 'properties/' . $propertyId;
    }

    protected function matchPropertyByDomain(string $token, array $properties, string $domain): ?string
    {
        $domain = strtolower(trim($domain));
        if ($domain === '') {
            return null;
        }

        foreach ($properties as $property) {
            $propertyId = $property['property_id'] ?? null;
            if (!$propertyId) {
                continue;
            }

            try {
                $streams = Http::withHeaders([
                    'Authorization' => "Bearer {$token}",
                ])->get("https://analyticsadmin.googleapis.com/v1beta/{$propertyId}/dataStreams");

                if (!$streams->successful()) {
                    continue;
                }

                $items = $streams->json('dataStreams', []);
                foreach ($items as $stream) {
                    $uri = $stream['webStreamData']['defaultUri'] ?? null;
                    if (!$uri) {
                        continue;
                    }

                    $host = parse_url($uri, PHP_URL_HOST);
                    if ($host && strtolower($host) === $domain) {
                        return $propertyId;
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    protected function runReport(string $token, string $propertyId, array $payload): array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->post("https://analyticsdata.googleapis.com/v1beta/{$propertyId}:runReport", $payload);

        if (!$response->successful()) {
            throw new \Exception('GA4 report failed: ' . $response->body());
        }

        return $response->json();
    }

    protected function runReportWithSourceFallback(string $token, string $propertyId, string $startDate, string $endDate): array
    {
        $payload = [
            'dateRanges' => [
                ['startDate' => $startDate, 'endDate' => $endDate],
            ],
            'dimensions' => [
                ['name' => 'sessionSourceMedium'],
            ],
            'metrics' => [
                ['name' => 'sessions'],
                ['name' => 'activeUsers'],
            ],
            'orderBys' => [
                ['metric' => ['metricName' => 'sessions'], 'desc' => true],
            ],
            'limit' => 10,
        ];

        try {
            return $this->runReport($token, $propertyId, $payload);
        } catch (\Exception $e) {
            $payload['dimensions'] = [
                ['name' => 'sourceMedium'],
            ];

            return $this->runReport($token, $propertyId, $payload);
        }
    }

    protected function formatSummary(array $report): array
    {
        $metrics = $report['metricHeaders'] ?? [];
        $row = $report['rows'][0]['metricValues'] ?? [];

        $values = [];
        foreach ($metrics as $index => $metric) {
            $name = $metric['name'] ?? null;
            $values[$name] = $row[$index]['value'] ?? null;
        }

        return [
            'active_users' => isset($values['activeUsers']) ? (int) $values['activeUsers'] : null,
            'sessions' => isset($values['sessions']) ? (int) $values['sessions'] : null,
            'engagement_rate' => isset($values['engagementRate']) ? (float) $values['engagementRate'] : null,
            'avg_session_duration' => isset($values['averageSessionDuration']) ? (float) $values['averageSessionDuration'] : null,
            'conversions' => isset($values['conversions']) ? (int) $values['conversions'] : null,
        ];
    }

    protected function formatRows(array $report, array $dimensions, array $metrics, array $mapping): array
    {
        $rows = $report['rows'] ?? [];
        $dimensionHeaders = collect($report['dimensionHeaders'] ?? [])->pluck('name')->all();
        $metricHeaders = collect($report['metricHeaders'] ?? [])->pluck('name')->all();

        return collect($rows)->map(function ($row) use ($dimensionHeaders, $metricHeaders, $mapping) {
            $dimensionValues = $row['dimensionValues'] ?? [];
            $metricValues = $row['metricValues'] ?? [];

            $data = [];

            foreach ($mapping as $key => $source) {
                $dimIndex = array_search($source, $dimensionHeaders, true);
                if ($dimIndex !== false) {
                    $data[$key] = $dimensionValues[$dimIndex]['value'] ?? null;
                    continue;
                }

                $metricIndex = array_search($source, $metricHeaders, true);
                if ($metricIndex !== false) {
                    $value = $metricValues[$metricIndex]['value'] ?? null;
                    $data[$key] = is_numeric($value) ? (float) $value : $value;
                }
            }

            if (isset($data['sessions'])) {
                $data['sessions'] = (int) $data['sessions'];
            }
            if (isset($data['active_users'])) {
                $data['active_users'] = (int) $data['active_users'];
            }

            return $data;
        })->values()->all();
    }
}
