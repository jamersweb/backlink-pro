<?php

namespace App\Services\Meta\Connectors;

use App\Models\Domain;
use App\Models\DomainMetaConnector;
use App\Models\DomainMetaPage;

class CustomJsMetaConnector implements MetaConnectorInterface
{
    /**
     * Test connection (always succeeds for custom JS)
     */
    public function testConnection(DomainMetaConnector $connector): array
    {
        return ['ok' => true, 'message' => 'Custom JS snippet ready'];
    }

    /**
     * List resources (returns empty - pages must be imported manually)
     */
    public function listResources(Domain $domain, DomainMetaConnector $connector): array
    {
        // Custom JS doesn't fetch from remote - pages are imported manually
        return [];
    }

    /**
     * Fetch meta (not applicable for custom JS)
     */
    public function fetchMeta(DomainMetaPage $page, DomainMetaConnector $connector): array
    {
        // Return published meta if available
        return $page->meta_published_json ?? [
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
     * Publish meta (stores in page, no remote update)
     */
    public function publishMeta(DomainMetaPage $page, array $meta, DomainMetaConnector $connector): array
    {
        // For custom JS, publishing means storing the meta config
        // The JS snippet will read it from the API endpoint
        return ['ok' => true, 'message' => 'Meta configuration saved'];
    }
}


