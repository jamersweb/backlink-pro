<?php

namespace App\Services\Meta\Connectors;

use App\Models\DomainConnector;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopifyConnector implements ConnectorInterface
{
    /**
     * Test connection using GraphQL
     */
    public function testConnection(DomainConnector $connector): array
    {
        try {
            $settings = $connector->settings_json ?? [];
            $credentials = $connector->credentials_json ?? [];
            
            $shop = $settings['shop'] ?? '';
            $accessToken = $credentials['access_token'] ?? '';
            $apiVersion = $settings['api_version'] ?? '2024-01';

            if (!$shop || !$accessToken) {
                return [
                    'ok' => false,
                    'error_code' => 'CONNECTOR_NOT_CONFIGURED',
                    'message' => 'Shop domain and access token are required',
                ];
            }

            // Normalize shop domain
            $shop = $this->normalizeShopDomain($shop);

            // Test with GraphQL shop query
            $query = 'query { shop { name } }';
            
            $response = Http::timeout(10)
                ->withHeaders([
                    'X-Shopify-Access-Token' => $accessToken,
                    'Content-Type' => 'application/json',
                ])
                ->post("https://{$shop}/admin/api/{$apiVersion}/graphql.json", [
                    'query' => $query,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['data']['shop']['name'])) {
                    return [
                        'ok' => true,
                        'message' => 'Connection successful',
                    ];
                }
            }

            if ($response->status() === 401) {
                return [
                    'ok' => false,
                    'error_code' => 'AUTH_FAILED',
                    'message' => 'Authentication failed. Please check your access token.',
                ];
            }

            return [
                'ok' => false,
                'error_code' => 'REMOTE_ERROR',
                'message' => 'Connection failed: ' . $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('Shopify connector test failed', [
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
            
            $shop = $this->normalizeShopDomain($settings['shop'] ?? '');
            $accessToken = $credentials['access_token'] ?? '';
            $apiVersion = $settings['api_version'] ?? '2024-01';

            // Extract handle from URL or use as-is
            $handle = $this->extractHandle($urlOrHandle);
            $resourceType = $this->detectResourceType($urlOrHandle);

            // For MVP, we'll fetch via REST API (simpler than GraphQL for reading)
            // In production, you might want to use GraphQL here too
            $gid = $this->resolveGid($handle, $resourceType, $shop, $accessToken, $apiVersion);

            if (!$gid) {
                throw new \Exception('Could not resolve Shopify resource from: ' . $urlOrHandle);
            }

            // Fetch via GraphQL to get SEO fields
            $query = $this->buildFetchQuery($resourceType, $gid);
            
            $response = Http::timeout(10)
                ->withHeaders([
                    'X-Shopify-Access-Token' => $accessToken,
                    'Content-Type' => 'application/json',
                ])
                ->post("https://{$shop}/admin/api/{$apiVersion}/graphql.json", [
                    'query' => $query,
                ]);

            if (!$response->successful()) {
                throw new \Exception('Failed to fetch meta: ' . $response->body());
            }

            $data = $response->json();
            $resource = $this->extractResource($data, $resourceType);

            return [
                'title' => $resource['seo']['title'] ?? $resource['title'] ?? '',
                'description' => $resource['seo']['description'] ?? '',
                'og_title' => $resource['seo']['title'] ?? $resource['title'] ?? '',
                'og_description' => $resource['seo']['description'] ?? '',
                'og_image' => $resource['image']['url'] ?? '',
                'canonical' => $resource['onlineStoreUrl'] ?? '',
                'robots' => 'index,follow',
            ];
        } catch (\Exception $e) {
            Log::error('Shopify fetch meta failed', [
                'error' => $e->getMessage(),
                'url' => $urlOrHandle,
            ]);
            throw $e;
        }
    }

    /**
     * Publish meta using GraphQL
     */
    public function publishMeta(string $urlOrHandle, array $meta, DomainConnector $connector): array
    {
        try {
            $settings = $connector->settings_json ?? [];
            $credentials = $connector->credentials_json ?? [];
            
            $shop = $this->normalizeShopDomain($settings['shop'] ?? '');
            $accessToken = $credentials['access_token'] ?? '';
            $apiVersion = $settings['api_version'] ?? '2024-01';

            $handle = $this->extractHandle($urlOrHandle);
            $resourceType = $this->detectResourceType($urlOrHandle);

            // Resolve GID
            $gid = $this->resolveGid($handle, $resourceType, $shop, $accessToken, $apiVersion);

            if (!$gid) {
                return [
                    'ok' => false,
                    'error_code' => 'RESOURCE_NOT_FOUND',
                    'message' => 'Could not resolve Shopify resource from: ' . $urlOrHandle,
                ];
            }

            // Build GraphQL mutation
            $mutation = $this->buildPublishMutation($resourceType, $gid, $meta);
            
            $response = Http::timeout(30)
                ->withHeaders([
                    'X-Shopify-Access-Token' => $accessToken,
                    'Content-Type' => 'application/json',
                ])
                ->post("https://{$shop}/admin/api/{$apiVersion}/graphql.json", [
                    'query' => $mutation,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['errors'])) {
                    return [
                        'ok' => false,
                        'error_code' => 'REMOTE_ERROR',
                        'message' => implode(', ', array_column($data['errors'], 'message')),
                    ];
                }

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
            Log::error('Shopify publish meta failed', [
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
        return in_array($resourceType, ['page', 'article', 'product', 'collection']);
    }

    /**
     * Normalize shop domain (add .myshopify.com if needed)
     */
    protected function normalizeShopDomain(string $shop): string
    {
        $shop = strtolower(trim($shop));
        if (!str_contains($shop, '.')) {
            $shop .= '.myshopify.com';
        }
        return $shop;
    }

    /**
     * Extract handle from URL
     */
    protected function extractHandle(string $urlOrHandle): string
    {
        $path = parse_url($urlOrHandle, PHP_URL_PATH) ?? $urlOrHandle;
        return basename(trim($path, '/'));
    }

    /**
     * Detect resource type from URL
     */
    protected function detectResourceType(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?? $url;
        
        if (str_contains($path, '/blogs/')) {
            return 'article';
        }
        if (str_contains($path, '/pages/')) {
            return 'page';
        }
        if (str_contains($path, '/products/')) {
            return 'product';
        }
        if (str_contains($path, '/collections/')) {
            return 'collection';
        }
        
        return 'page'; // Default
    }

    /**
     * Resolve Shopify GID from handle
     */
    protected function resolveGid(string $handle, string $resourceType, string $shop, string $accessToken, string $apiVersion): ?string
    {
        // For MVP, use REST API to get ID, then convert to GID
        $restEndpoint = match($resourceType) {
            'page' => 'pages',
            'article' => 'articles', // Need blog handle too, simplified for MVP
            default => null,
        };

        if (!$restEndpoint) {
            return null;
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'X-Shopify-Access-Token' => $accessToken,
                ])
                ->get("https://{$shop}/admin/api/{$apiVersion}/{$restEndpoint}.json", [
                    'handle' => $handle,
                    'limit' => 1,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $resources = $data[$restEndpoint] ?? [];
                if (!empty($resources) && isset($resources[0]['id'])) {
                    // Convert to GID format: gid://shopify/Page/{id}
                    $type = ucfirst(rtrim($restEndpoint, 's'));
                    return "gid://shopify/{$type}/{$resources[0]['id']}";
                }
            }
        } catch (\Exception $e) {
            Log::error('Shopify resolve GID failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Build GraphQL fetch query
     */
    protected function buildFetchQuery(string $resourceType, string $gid): string
    {
        $queryType = match($resourceType) {
            'page' => 'page',
            'article' => 'article',
            default => 'page',
        };

        return "query {
  {$queryType}(id: \"{$gid}\") {
    id
    title
    seo {
      title
      description
    }
    onlineStoreUrl
  }
}";
    }

    /**
     * Build GraphQL publish mutation
     */
    protected function buildPublishMutation(string $resourceType, string $gid, array $meta): string
    {
        $mutationName = match($resourceType) {
            'page' => 'pageUpdate',
            'article' => 'articleUpdate',
            default => 'pageUpdate',
        };

        $inputType = match($resourceType) {
            'page' => 'PageInput',
            'article' => 'ArticleInput',
            default => 'PageInput',
        };

        $seoTitle = $meta['title'] ?? '';
        $seoDescription = $meta['description'] ?? '';

        return <<<GRAPHQL
mutation {
  {$mutationName}({$resourceType}: {id: "{$gid}", seo: {title: "{$seoTitle}", description: "{$seoDescription}"}}) {
    {$resourceType} {
      id
      seo {
        title
        description
      }
    }
    userErrors {
      field
      message
    }
  }
}
GRAPHQL;
    }

    /**
     * Extract resource from GraphQL response
     */
    protected function extractResource(array $data, string $resourceType): array
    {
        $queryType = match($resourceType) {
            'page' => 'page',
            'article' => 'article',
            default => 'page',
        };

        return $data['data'][$queryType] ?? [];
    }
}

