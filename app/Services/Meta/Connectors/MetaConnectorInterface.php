<?php

namespace App\Services\Meta\Connectors;

use App\Models\Domain;
use App\Models\DomainMetaConnector;
use App\Models\DomainMetaPage;

interface MetaConnectorInterface
{
    /**
     * Test connection to the connector
     */
    public function testConnection(DomainMetaConnector $connector): array;

    /**
     * List available resources/pages
     */
    public function listResources(Domain $domain, DomainMetaConnector $connector): array;

    /**
     * Fetch current meta for a page
     */
    public function fetchMeta(DomainMetaPage $page, DomainMetaConnector $connector): array;

    /**
     * Publish meta changes to the connector
     */
    public function publishMeta(DomainMetaPage $page, array $meta, DomainMetaConnector $connector): array;
}


