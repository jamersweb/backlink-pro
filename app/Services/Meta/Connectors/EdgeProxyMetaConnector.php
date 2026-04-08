<?php

namespace App\Services\Meta\Connectors;

use App\Models\Domain;
use App\Models\DomainMetaPage;
use App\Models\DomainMetaConnector;

/**
 * Edge/Proxy mode: meta is published to DB; Worker fetches via /edge/meta.
 * No remote API call from this app.
 */
class EdgeProxyMetaConnector implements MetaConnectorInterface
{
    public function testConnection(DomainMetaConnector $connector): array
    {
        return [
            'ok' => true,
            'message' => 'Edge/Proxy mode ready. Deploy Worker to activate.',
        ];
    }

    public function listResources(Domain $domain, DomainMetaConnector $connector): array
    {
        return [];
    }

    public function fetchMeta(DomainMetaPage $page, DomainMetaConnector $connector): array
    {
        return $page->meta_published_json ?? $page->meta_current_json ?? [];
    }

    public function publishMeta(DomainMetaPage $page, array $meta, DomainMetaConnector $connector): array
    {
        return [
            'ok' => true,
            'message' => 'Edge/Proxy mode ready. Deploy Worker to activate.',
        ];
    }
}
