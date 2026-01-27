<?php

namespace App\Services\Meta\Connectors;

use App\Models\Domain;
use App\Models\DomainMetaConnector;
use App\Models\DomainMetaPage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopifyMetaConnector implements MetaConnectorInterface
{
    protected $shopDomain;
    protected $token;
    protected $apiVersion;

    /**
     * Test connection
     */
    public function testConnection(DomainMetaConnector $connector): array
    {
        try {
            $auth = $connector->auth_json ?? [];
            $this->shopDomain = $auth['shop_domain'] ?? '';
            $this->token = $auth['admin_access_token'] ?? null;
            $this->apiVersion = $auth['api_version'] ?? '2024-01';

            if (!$this->token || !$this->shopDomain) {
                return ['ok' => false, 'message' => 'Shop domain and admin token are required'];
            }

            $response = Http::timeout(10)
                ->withHeaders([
                    'X-Shopify-Access-Token' => $this->token,
                ])
                ->get("https://{$this->shopDomain}/admin/api/{$this->apiVersion}/shop.json");

            if ($response->successful()) {
                return ['ok' => true, 'message' => 'Connection successful'];
            }

            return ['ok' => false, 'message' => 'Connection failed: ' . $response->body()];
        } catch (\Exception $e) {
            Log::error('Shopify connector test failed', ['error' => $e->getMessage()]);
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * List resources
     */
    public function listResources(Domain $domain, DomainMetaConnector $connector): array
    {
        try {
            $auth = $connector->auth_json ?? [];
            $this->shopDomain = $auth['shop_domain'] ?? '';
            $this->token = $auth['admin_access_token'] ?? null;
            $this->apiVersion = $auth['api_version'] ?? '2024-01';

            $resources = [];

            // Fetch products
            $products = $this->fetchShopifyResources('products');
            foreach ($products as $product) {
                $resources[] = [
                    'external_id' => (string)$product['id'],
                    'resource_type' => DomainMetaPage::RESOURCE_TYPE_SHOPIFY_PRODUCT,
                    'url' => "https://{$this->shopDomain}/products/{$product['handle']}",
                    'path' => "/products/{$product['handle']}",
                    'title_current' => $product['title'] ?? '',
                ];
            }

            // Fetch pages
            $pages = $this->fetchShopifyResources('pages');
            foreach ($pages as $page) {
                $resources[] = [
                    'external_id' => (string)$page['id'],
                    'resource_type' => DomainMetaPage::RESOURCE_TYPE_SHOPIFY_PAGE,
                    'url' => "https://{$this->shopDomain}/pages/{$page['handle']}",
                    'path' => "/pages/{$page['handle']}",
                    'title_current' => $page['title'] ?? '',
                ];
            }

            // Fetch collections
            $collections = $this->fetchShopifyResources('collections');
            foreach ($collections as $collection) {
                $resources[] = [
                    'external_id' => (string)$collection['id'],
                    'resource_type' => DomainMetaPage::RESOURCE_TYPE_SHOPIFY_COLLECTION,
                    'url' => "https://{$this->shopDomain}/collections/{$collection['handle']}",
                    'path' => "/collections/{$collection['handle']}",
                    'title_current' => $collection['title'] ?? '',
                ];
            }

            return $resources;
        } catch (\Exception $e) {
            Log::error('Shopify list resources failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Fetch Shopify resources with pagination
     */
    protected function fetchShopifyResources(string $resourceType): array
    {
        $allItems = [];
        $url = "https://{$this->shopDomain}/admin/api/{$this->apiVersion}/{$resourceType}.json?limit=250";

        do {
            $response = Http::timeout(30)
                ->withHeaders([
                    'X-Shopify-Access-Token' => $this->token,
                ])
                ->get($url);

            if (!$response->successful()) {
                break;
            }

            $data = $response->json();
            $items = $data[$resourceType] ?? [];
            $allItems = array_merge($allItems, $items);

            // Check for next page
            $linkHeader = $response->header('Link');
            if ($linkHeader && str_contains($linkHeader, 'rel="next"')) {
                preg_match('/<([^>]+)>; rel="next"/', $linkHeader, $matches);
                $url = $matches[1] ?? null;
            } else {
                $url = null;
            }
        } while ($url);

        return $allItems;
    }

    /**
     * Fetch meta
     */
    public function fetchMeta(DomainMetaPage $page, DomainMetaConnector $connector): array
    {
        try {
            $auth = $connector->auth_json ?? [];
            $this->shopDomain = $auth['shop_domain'] ?? '';
            $this->token = $auth['admin_access_token'] ?? null;
            $this->apiVersion = $auth['api_version'] ?? '2024-01';

            $resourceType = $page->resource_type;
            $resourceId = $page->external_id;

            $endpoint = match($resourceType) {
                DomainMetaPage::RESOURCE_TYPE_SHOPIFY_PRODUCT => "products/{$resourceId}",
                DomainMetaPage::RESOURCE_TYPE_SHOPIFY_PAGE => "pages/{$resourceId}",
                DomainMetaPage::RESOURCE_TYPE_SHOPIFY_COLLECTION => "collections/{$resourceId}",
                default => null,
            };

            if (!$endpoint) {
                throw new \Exception("Unsupported resource type: {$resourceType}");
            }

            $response = Http::timeout(10)
                ->withHeaders([
                    'X-Shopify-Access-Token' => $this->token,
                ])
                ->get("https://{$this->shopDomain}/admin/api/{$this->apiVersion}/{$endpoint}.json");

            if (!$response->successful()) {
                throw new \Exception('Failed to fetch meta: ' . $response->body());
            }

            $data = $response->json();
            $resource = $data[substr($resourceType, 8)] ?? []; // Remove 'shopify_' prefix

            return [
                'title' => $resource['title'] ?? '',
                'description' => $resource['body_html'] ?? '',
                'og_title' => $resource['metafields_global_title_tag'] ?? $resource['title'] ?? '',
                'og_description' => $resource['metafields_global_description_tag'] ?? '',
                'og_image' => $resource['image']['src'] ?? '',
                'canonical' => $page->url ?? '',
                'robots' => 'index,follow',
            ];
        } catch (\Exception $e) {
            Log::error('Shopify fetch meta failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Publish meta
     */
    public function publishMeta(DomainMetaPage $page, array $meta, DomainMetaConnector $connector): array
    {
        try {
            $auth = $connector->auth_json ?? [];
            $this->shopDomain = $auth['shop_domain'] ?? '';
            $this->token = $auth['admin_access_token'] ?? null;
            $this->apiVersion = $auth['api_version'] ?? '2024-01';

            $resourceType = $page->resource_type;
            $resourceId = $page->external_id;

            $endpoint = match($resourceType) {
                DomainMetaPage::RESOURCE_TYPE_SHOPIFY_PRODUCT => "products/{$resourceId}",
                DomainMetaPage::RESOURCE_TYPE_SHOPIFY_PAGE => "pages/{$resourceId}",
                DomainMetaPage::RESOURCE_TYPE_SHOPIFY_COLLECTION => "collections/{$resourceId}",
                default => null,
            };

            if (!$endpoint) {
                throw new \Exception("Unsupported resource type: {$resourceType}");
            }

            // Build update payload
            $payload = [
                substr($resourceType, 8) => [ // Remove 'shopify_' prefix
                    'metafields_global_title_tag' => $meta['og_title'] ?? $meta['title'] ?? '',
                    'metafields_global_description_tag' => $meta['og_description'] ?? $meta['description'] ?? '',
                ],
            ];

            $response = Http::timeout(30)
                ->withHeaders([
                    'X-Shopify-Access-Token' => $this->token,
                ])
                ->put("https://{$this->shopDomain}/admin/api/{$this->apiVersion}/{$endpoint}.json", $payload);

            if (!$response->successful()) {
                throw new \Exception('Failed to publish meta: ' . $response->body());
            }

            return ['ok' => true, 'message' => 'Meta published successfully'];
        } catch (\Exception $e) {
            Log::error('Shopify publish meta failed', ['error' => $e->getMessage()]);
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }
}


