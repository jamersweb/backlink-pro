<?php

namespace App\Services\Meta\Connectors;

use App\Models\DomainConnector;

interface ConnectorInterface
{
    /**
     * Test connection to the connector
     */
    public function testConnection(DomainConnector $connector): array;

    /**
     * Fetch page meta from the connector
     * 
     * @param string $urlOrHandle URL or handle identifier
     * @param DomainConnector $connector
     * @return array Meta data (title, description, og_title, etc.)
     */
    public function fetchPageMeta(string $urlOrHandle, DomainConnector $connector): array;

    /**
     * Publish meta to the connector
     * 
     * @param string $urlOrHandle URL or handle identifier
     * @param array $meta Meta data to publish
     * @param DomainConnector $connector
     * @return array Result with 'ok' boolean and 'message'
     */
    public function publishMeta(string $urlOrHandle, array $meta, DomainConnector $connector): array;

    /**
     * Check if connector supports a resource type
     */
    public function supports(string $resourceType): bool;
}


