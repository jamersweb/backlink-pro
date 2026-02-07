<?php

namespace App\Services\SEO;

interface SerpProviderInterface
{
    /**
     * Get rank for keyword
     */
    public function getRank(string $keyword, string $domain, string $country = 'PK', string $device = 'desktop'): ?array;
}
