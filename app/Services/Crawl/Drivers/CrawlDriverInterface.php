<?php

namespace App\Services\Crawl\Drivers;

interface CrawlDriverInterface
{
    /**
     * Check if driver supports a task type
     */
    public function supports(string $taskType): bool;

    /**
     * Validate provider settings
     */
    public function validateSettings(array $settings): array; // ['ok' => bool, 'message' => string]

    /**
     * Execute a task
     */
    public function execute(array $taskPayload): array;

    /**
     * Estimate cost for a task
     */
    public function estimateCost(array $taskPayload): array; // ['units' => float, 'unit_name' => string, 'cents' => int]
}


