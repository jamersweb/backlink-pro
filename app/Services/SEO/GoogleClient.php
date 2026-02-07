<?php

namespace App\Services\SEO;

use App\Models\OauthConnection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleClient
{
    protected OauthConnection $connection;

    public function __construct(OauthConnection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Get access token (refresh if needed)
     */
    protected function getAccessToken(): string
    {
        // Check if token needs refresh
        if ($this->connection->expires_at && $this->connection->expires_at->isPast()) {
            $this->refreshToken();
        }

        return $this->connection->access_token;
    }

    /**
     * Refresh access token
     */
    protected function refreshToken(): void
    {
        try {
            $refreshToken = $this->connection->refresh_token;

            $response = Http::post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
            ]);

            if (!$response->successful()) {
                throw new \Exception("Token refresh failed: " . $response->body());
            }

            $data = $response->json();
            
            $this->connection->access_token = $data['access_token'];
            $this->connection->expires_at = now()->addSeconds($data['expires_in']);
            $this->connection->save();

        } catch (\Exception $e) {
            Log::error('Google token refresh failed', [
                'connection_id' => $this->connection->id,
                'error' => $e->getMessage(),
            ]);

            $this->connection->update([
                'status' => 'error',
                'last_error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Fetch GSC sites
     */
    public function fetchGscSites(): array
    {
        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->get('https://www.googleapis.com/webmasters/v3/sites');

        if (!$response->successful()) {
            throw new \Exception("Failed to fetch GSC sites: " . $response->body());
        }

        return $response->json()['siteEntry'] ?? [];
    }

    /**
     * Fetch GSC daily metrics
     */
    public function fetchGscDailyMetrics(string $siteUrl, string $startDate, string $endDate): array
    {
        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->post("https://www.googleapis.com/webmasters/v3/sites/" . urlencode($siteUrl) . "/searchAnalytics/query", [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'dimensions' => ['date'],
            'rowLimit' => 10000,
        ]);

        if (!$response->successful()) {
            throw new \Exception("Failed to fetch GSC metrics: " . $response->body());
        }

        return $response->json()['rows'] ?? [];
    }

    /**
     * Fetch GSC top queries
     */
    public function fetchGscTopQueries(string $siteUrl, string $startDate, string $endDate, int $limit = 1000): array
    {
        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->post("https://www.googleapis.com/webmasters/v3/sites/" . urlencode($siteUrl) . "/searchAnalytics/query", [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'dimensions' => ['query'],
            'rowLimit' => $limit,
        ]);

        if (!$response->successful()) {
            throw new \Exception("Failed to fetch GSC queries: " . $response->body());
        }

        return $response->json()['rows'] ?? [];
    }

    /**
     * Fetch GSC top pages
     */
    public function fetchGscTopPages(string $siteUrl, string $startDate, string $endDate, int $limit = 1000): array
    {
        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->post("https://www.googleapis.com/webmasters/v3/sites/" . urlencode($siteUrl) . "/searchAnalytics/query", [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'dimensions' => ['page'],
            'rowLimit' => $limit,
        ]);

        if (!$response->successful()) {
            throw new \Exception("Failed to fetch GSC pages: " . $response->body());
        }

        return $response->json()['rows'] ?? [];
    }

    /**
     * Fetch GA4 properties
     */
    public function fetchGa4Properties(): array
    {
        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->get('https://analyticsadmin.googleapis.com/v1beta/accounts/-/properties');

        if (!$response->successful()) {
            throw new \Exception("Failed to fetch GA4 properties: " . $response->body());
        }

        return $response->json()['properties'] ?? [];
    }

    /**
     * Fetch GA4 daily metrics
     */
    public function fetchGa4DailyMetrics(string $propertyId, string $startDate, string $endDate): array
    {
        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->post("https://analyticsdata.googleapis.com/v1beta/properties/{$propertyId}:runReport", [
            'dateRanges' => [
                ['startDate' => $startDate, 'endDate' => $endDate],
            ],
            'dimensions' => [
                ['name' => 'date'],
            ],
            'metrics' => [
                ['name' => 'sessions'],
                ['name' => 'activeUsers'],
                ['name' => 'newUsers'],
                ['name' => 'engagementRate'],
                ['name' => 'averageSessionDuration'],
                ['name' => 'screenPageViews'],
                ['name' => 'conversions'],
                ['name' => 'totalRevenue'],
            ],
        ]);

        if (!$response->successful()) {
            throw new \Exception("Failed to fetch GA4 metrics: " . $response->body());
        }

        return $response->json()['rows'] ?? [];
    }

    /**
     * Fetch GA4 top pages
     */
    public function fetchGa4TopPages(string $propertyId, string $startDate, string $endDate, int $limit = 100): array
    {
        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->post("https://analyticsdata.googleapis.com/v1beta/properties/{$propertyId}:runReport", [
            'dateRanges' => [
                ['startDate' => $startDate, 'endDate' => $endDate],
            ],
            'dimensions' => [
                ['name' => 'date'],
                ['name' => 'pagePath'],
                ['name' => 'pageTitle'],
            ],
            'metrics' => [
                ['name' => 'screenPageViews'],
                ['name' => 'activeUsers'],
                ['name' => 'conversions'],
            ],
            'limit' => $limit,
        ]);

        if (!$response->successful()) {
            throw new \Exception("Failed to fetch GA4 pages: " . $response->body());
        }

        return $response->json()['rows'] ?? [];
    }
}
