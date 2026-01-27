<?php

namespace App\Services\Backlinks;

interface BacklinkProviderInterface
{
    /**
     * Fetch summary data for a host
     */
    public function fetchSummary(string $host): array;

    /**
     * Fetch backlinks for a host
     * Returns: ['items' => [...], 'total' => int]
     */
    public function fetchBacklinks(string $host, int $limit, int $offset = 0): array;

    /**
     * Fetch referring domains for a host
     * Returns: ['items' => [...], 'total' => int]
     */
    public function fetchRefDomains(string $host, int $limit, int $offset = 0): array;

    /**
     * Fetch anchor text summaries for a host
     * Returns: ['items' => [...], 'total' => int]
     */
    public function fetchAnchors(string $host, int $limit, int $offset = 0): array;
}


