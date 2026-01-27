<?php

namespace App\Services\Meta\Connectors;

use App\Models\Domain;
use App\Models\DomainMetaConnector;
use App\Models\DomainMetaPage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WordPressMetaConnector implements MetaConnectorInterface
{
    protected $baseUrl;
    protected $token;

    /**
     * Test connection
     */
    public function testConnection(DomainMetaConnector $connector): array
    {
        try {
            $this->baseUrl = rtrim($connector->base_url, '/');
            $auth = $connector->auth_json ?? [];
            $this->token = $auth['token'] ?? null;

            if (!$this->token) {
                return ['ok' => false, 'message' => 'API token is required'];
            }

            $response = Http::timeout(10)
                ->withToken($this->token)
                ->get("{$this->baseUrl}/wp-json/backlinkpro/v1/ping");

            if ($response->successful()) {
                return ['ok' => true, 'message' => 'Connection successful'];
            }

            return ['ok' => false, 'message' => 'Connection failed: ' . $response->body()];
        } catch (\Exception $e) {
            Log::error('WordPress connector test failed', ['error' => $e->getMessage()]);
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * List resources
     */
    public function listResources(Domain $domain, DomainMetaConnector $connector): array
    {
        try {
            $this->baseUrl = rtrim($connector->base_url, '/');
            $auth = $connector->auth_json ?? [];
            $this->token = $auth['token'] ?? null;

            $response = Http::timeout(30)
                ->withToken($this->token)
                ->get("{$this->baseUrl}/wp-json/backlinkpro/v1/resources");

            if (!$response->successful()) {
                throw new \Exception('Failed to fetch resources: ' . $response->body());
            }

            $data = $response->json();
            $resources = [];

            foreach ($data['items'] ?? [] as $item) {
                $resources[] = [
                    'external_id' => (string)$item['id'],
                    'resource_type' => $item['type'] === 'page' ? DomainMetaPage::RESOURCE_TYPE_WP_PAGE : DomainMetaPage::RESOURCE_TYPE_WP_POST,
                    'url' => $item['url'] ?? '',
                    'path' => parse_url($item['url'] ?? '', PHP_URL_PATH),
                    'title_current' => $item['title'] ?? '',
                ];
            }

            return $resources;
        } catch (\Exception $e) {
            Log::error('WordPress list resources failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Fetch meta
     */
    public function fetchMeta(DomainMetaPage $page, DomainMetaConnector $connector): array
    {
        try {
            $this->baseUrl = rtrim($connector->base_url, '/');
            $auth = $connector->auth_json ?? [];
            $this->token = $auth['token'] ?? null;

            $response = Http::timeout(10)
                ->withToken($this->token)
                ->get("{$this->baseUrl}/wp-json/backlinkpro/v1/meta/{$page->external_id}");

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
            Log::error('WordPress fetch meta failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Publish meta
     */
    public function publishMeta(DomainMetaPage $page, array $meta, DomainMetaConnector $connector): array
    {
        try {
            $this->baseUrl = rtrim($connector->base_url, '/');
            $auth = $connector->auth_json ?? [];
            $this->token = $auth['token'] ?? null;

            $payload = [
                'title' => $meta['title'] ?? '',
                'description' => $meta['description'] ?? '',
                'og_title' => $meta['og_title'] ?? '',
                'og_description' => $meta['og_description'] ?? '',
                'og_image' => $meta['og_image'] ?? '',
                'canonical' => $meta['canonical'] ?? '',
                'robots' => $meta['robots'] ?? 'index,follow',
            ];

            $response = Http::timeout(30)
                ->withToken($this->token)
                ->post("{$this->baseUrl}/wp-json/backlinkpro/v1/meta/{$page->external_id}", $payload);

            if (!$response->successful()) {
                throw new \Exception('Failed to publish meta: ' . $response->body());
            }

            return ['ok' => true, 'message' => 'Meta published successfully'];
        } catch (\Exception $e) {
            Log::error('WordPress publish meta failed', ['error' => $e->getMessage()]);
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }
}


