<?php

namespace App\Services\Meta\Connectors;

use App\Models\DomainConnector;

class ConnectorFactory
{
    /**
     * Create connector instance based on type
     */
    public static function make(string $type): ConnectorInterface
    {
        return match($type) {
            DomainConnector::TYPE_WP => new WordPressConnector(),
            DomainConnector::TYPE_SHOPIFY => new ShopifyConnector(),
            DomainConnector::TYPE_GENERIC => new GenericRestConnector(),
            default => throw new \InvalidArgumentException("Unknown connector type: {$type}"),
        };
    }
}


