<?php

namespace App\Services\Meta\Connectors;

use App\Services\Meta\Connectors\WordPressMetaConnector;
use App\Services\Meta\Connectors\ShopifyMetaConnector;
use App\Services\Meta\Connectors\CustomJsMetaConnector;
use App\Services\Meta\Connectors\EdgeProxyMetaConnector;

class MetaConnectorFactory
{
    public static function make(string $type): MetaConnectorInterface
    {
        switch ($type) {
            case 'wordpress':
                return new WordPressMetaConnector();
            case 'shopify':
                return new ShopifyMetaConnector();
            case 'custom_js':
                return new CustomJsMetaConnector();
            case 'edge_proxy':
                return new EdgeProxyMetaConnector();
            default:
                throw new \Exception("Unknown meta connector type: {$type}");
        }
    }
}


