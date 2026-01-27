<?php

namespace App\Services\Meta\Connectors;

use App\Models\DomainConnector;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenericRestConnector implements ConnectorInterface
{
    /**
     * Test connection
     */
    public function testConnection(DomainConnector $connector): array
    {
        try {
            $settings = $connector->settings_json ?? [];
            $credentials = $connector->credentials_json ?? [];
            
            $baseUrl = rtrim($settings['base_url'] ?? '', '/');
            $testEndpoint = $settings['test_endpoint'] ?? '/health';

            if (!$baseUrl) {
                return [
                    'ok' => false,
                    'error_code' => 'CONNECTOR_NOT_CONFIGURED',
                    'message' => 'Base URL is required',
                ];
            }

            $request = Http::timeout(10);

            // Add authentication
            $request = $this->addAuth($request, $settings, $credentials);

            $response = $request->get($baseUrl . $testEndpoint);

            if ($response->successful()) {
                return [
                    'ok' => true,
                    'message' => 'Connection successful',
                ];
            }

            if ($response->status() === 401) {
                return [
                    'ok' => false,
                    'error_code' => 'AUTH_FAILED',
                    'message' => 'Authentication failed',
                ];
            }

            return [
                'ok' => false,
                'error_code' => 'REMOTE_ERROR',
                'message' => 'Connection failed: ' . $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('Generic REST connector test failed', [
                'error' => $e->getMessage(),
                'connector_id' => $connector->id,
            ]);

            return [
                'ok' => false,
                'error_code' => 'UNKNOWN',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Fetch page meta
     */
    public function fetchPageMeta(string $urlOrHandle, DomainConnector $connector): array
    {
        // Generic REST connector doesn't support fetching meta in MVP
        // User's CMS must implement this endpoint if needed
        return [
            'title' => '',
            'description' => '',
            'og_title' => '',
            'og_description' => '',
            'og_image' => '',
            'canonical' => '',
            'robots' => 'index,follow',
        ];
    }

    /**
     * Publish meta
     */
    public function publishMeta(string $urlOrHandle, array $meta, DomainConnector $connector): array
    {
        try {
            $settings = $connector->settings_json ?? [];
            $credentials = $connector->credentials_json ?? [];
            
            $baseUrl = rtrim($settings['base_url'] ?? '', '/');
            $publishEndpoint = $settings['publish_endpoint'] ?? '/meta/update';

            if (!$baseUrl) {
                return [
                    'ok' => false,
                    'error_code' => 'CONNECTOR_NOT_CONFIGURED',
                    'message' => 'Base URL is required',
                ];
            }

            $payload = [
                'url' => $urlOrHandle,
                'title' => $meta['title'] ?? '',
                'description' => $meta['description'] ?? '',
                'canonical' => $meta['canonical'] ?? '',
            ];

            $request = Http::timeout(30);

            // Add authentication
            $request = $this->addAuth($request, $settings, $credentials);

            $response = $request->post($baseUrl . $publishEndpoint, $payload);

            if ($response->successful()) {
                return [
                    'ok' => true,
                    'message' => 'Meta published successfully',
                ];
            }

            if ($response->status() === 401) {
                return [
                    'ok' => false,
                    'error_code' => 'AUTH_FAILED',
                    'message' => 'Authentication failed',
                ];
            }

            if ($response->status() === 429) {
                return [
                    'ok' => false,
                    'error_code' => 'RATE_LIMITED',
                    'message' => 'Rate limited',
                ];
            }

            return [
                'ok' => false,
                'error_code' => 'REMOTE_ERROR',
                'message' => 'Failed to publish meta: ' . $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('Generic REST publish meta failed', [
                'error' => $e->getMessage(),
                'url' => $urlOrHandle,
            ]);

            return [
                'ok' => false,
                'error_code' => 'UNKNOWN',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if supports resource type
     */
    public function supports(string $resourceType): bool
    {
        // Generic connector supports all types
        return true;
    }

    /**
     * Add authentication to HTTP request
     */
    protected function addAuth($request, array $settings, array $credentials)
    {
        $authType = $settings['auth_type'] ?? 'bearer';

        return match($authType) {
            'bearer' => $request->withToken($credentials['token'] ?? ''),
            'basic' => $request->withBasicAuth(
                $credentials['username'] ?? '',
                $credentials['password'] ?? ''
            ),
            'api_key' => $request->withHeaders([
                $settings['api_key_header'] ?? 'X-API-Key' => $credentials['api_key'] ?? '',
            ]),
            default => $request,
        };
    }
}


