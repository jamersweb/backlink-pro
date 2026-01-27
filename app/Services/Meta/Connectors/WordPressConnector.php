<?php

namespace App\Services\Meta\Connectors;

use App\Models\DomainConnector;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WordPressConnector implements ConnectorInterface
{
    /**
     * Test connection using WordPress Application Passwords
     */
    public function testConnection(DomainConnector $connector): array
    {
        try {
            $settings = $connector->settings_json ?? [];
            $credentials = $connector->credentials_json ?? [];
            
            $baseUrl = rtrim($settings['wp_base_url'] ?? '', '/');
            $username = $credentials['username'] ?? '';
            $appPassword = $credentials['app_password'] ?? '';

            if (!$baseUrl || !$username || !$appPassword) {
                return [
                    'ok' => false,
                    'error_code' => 'CONNECTOR_NOT_CONFIGURED',
                    'message' => 'WordPress base URL, username, and application password are required',
                ];
            }

            // Test with WordPress REST API /users/me endpoint
            $response = Http::timeout(10)
                ->withBasicAuth($username, $appPassword)
                ->get("{$baseUrl}/wp-json/wp/v2/users/me");

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
                    'message' => 'Authentication failed. Please check your username and application password.',
                ];
            }

            return [
                'ok' => false,
                'error_code' => 'REMOTE_ERROR',
                'message' => 'Connection failed: ' . $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('WordPress connector test failed', [
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
        try {
            $settings = $connector->settings_json ?? [];
            $credentials = $connector->credentials_json ?? [];
            
            $baseUrl = rtrim($settings['wp_base_url'] ?? '', '/');
            $username = $credentials['username'] ?? '';
            $appPassword = $credentials['app_password'] ?? '';

            // Extract slug from URL or use as-is if it's a post ID
            $postId = is_numeric($urlOrHandle) ? (int)$urlOrHandle : $this->resolvePostId($urlOrHandle, $baseUrl, $username, $appPassword);

            if (!$postId) {
                throw new \Exception('Could not resolve WordPress post ID from: ' . $urlOrHandle);
            }

            // Fetch meta via plugin endpoint
            $response = Http::timeout(10)
                ->withBasicAuth($username, $appPassword)
                ->get("{$baseUrl}/wp-json/backlinkpro/v1/meta/{$postId}");

            if (!$response->successful()) {
                throw new \Exception('Failed to fetch meta: ' . $response->body());
            }

            $data = $response->json();

            return [
                'title' => $data['title'] ?? '',
                'description' => $data['description'] ?? '',
                'og_title' => $data['og_title'] ?? '',
                'og_description' => $data['og_description'] ?? '',
                'og_image' => $data['og_image'] ?? '',
                'canonical' => $data['canonical'] ?? '',
                'robots' => $data['robots'] ?? 'index,follow',
            ];
        } catch (\Exception $e) {
            Log::error('WordPress fetch meta failed', [
                'error' => $e->getMessage(),
                'url' => $urlOrHandle,
            ]);
            throw $e;
        }
    }

    /**
     * Publish meta
     */
    public function publishMeta(string $urlOrHandle, array $meta, DomainConnector $connector): array
    {
        try {
            $settings = $connector->settings_json ?? [];
            $credentials = $connector->credentials_json ?? [];
            
            $baseUrl = rtrim($settings['wp_base_url'] ?? '', '/');
            $username = $credentials['username'] ?? '';
            $appPassword = $credentials['app_password'] ?? '';

            // Resolve post ID
            $postId = is_numeric($urlOrHandle) ? (int)$urlOrHandle : $this->resolvePostId($urlOrHandle, $baseUrl, $username, $appPassword);

            if (!$postId) {
                return [
                    'ok' => false,
                    'error_code' => 'RESOURCE_NOT_FOUND',
                    'message' => 'Could not resolve WordPress post ID from: ' . $urlOrHandle,
                ];
            }

            $payload = [
                'title' => $meta['title'] ?? '',
                'description' => $meta['description'] ?? '',
                'og_title' => $meta['og_title'] ?? $meta['title'] ?? '',
                'og_description' => $meta['og_description'] ?? $meta['description'] ?? '',
                'og_image' => $meta['og_image'] ?? '',
                'canonical' => $meta['canonical'] ?? '',
                'robots' => $meta['robots'] ?? 'index,follow',
            ];

            $response = Http::timeout(30)
                ->withBasicAuth($username, $appPassword)
                ->post("{$baseUrl}/wp-json/backlinkpro/v1/meta/{$postId}", $payload);

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

            if ($response->status() === 404) {
                return [
                    'ok' => false,
                    'error_code' => 'RESOURCE_NOT_FOUND',
                    'message' => 'Post not found',
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
            Log::error('WordPress publish meta failed', [
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
        return in_array($resourceType, ['page', 'post', 'other']);
    }

    /**
     * Resolve WordPress post ID from URL path/slug
     */
    protected function resolvePostId(string $urlOrPath, string $baseUrl, string $username, string $appPassword): ?int
    {
        // Extract slug from URL
        $path = parse_url($urlOrPath, PHP_URL_PATH) ?? $urlOrPath;
        $slug = basename(trim($path, '/'));

        if (!$slug) {
            return null;
        }

        // Try pages first
        $response = Http::timeout(10)
            ->withBasicAuth($username, $appPassword)
            ->get("{$baseUrl}/wp-json/wp/v2/pages", [
                'slug' => $slug,
                'per_page' => 1,
            ]);

        if ($response->successful()) {
            $pages = $response->json();
            if (!empty($pages) && isset($pages[0]['id'])) {
                return (int)$pages[0]['id'];
            }
        }

        // Fallback to posts
        $response = Http::timeout(10)
            ->withBasicAuth($username, $appPassword)
            ->get("{$baseUrl}/wp-json/wp/v2/posts", [
                'slug' => $slug,
                'per_page' => 1,
            ]);

        if ($response->successful()) {
            $posts = $response->json();
            if (!empty($posts) && isset($posts[0]['id'])) {
                return (int)$posts[0]['id'];
            }
        }

        return null;
    }
}


