<?php

namespace App\Services\Crawl\Drivers;

use App\Services\Backlinks\BacklinkProviderFactory;
use Illuminate\Support\Facades\Log;

class BacklinksProviderDriver implements CrawlDriverInterface
{
    protected array $settings;

    public function __construct(array $settings = [])
    {
        $this->settings = $settings;
    }

    public function supports(string $taskType): bool
    {
        return $taskType === 'backlinks.provider';
    }

    public function validateSettings(array $settings): array
    {
        // Delegate to BacklinkProviderFactory
        try {
            $provider = BacklinkProviderFactory::make($settings['provider'] ?? 'dataforseo', $settings);
            // If provider can be instantiated, settings are valid
            return ['ok' => true, 'message' => 'Provider configured'];
        } catch (\Exception $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    public function execute(array $taskPayload): array
    {
        $host = $taskPayload['host'] ?? null;
        $limit = $taskPayload['limit'] ?? 1000;
        $offset = $taskPayload['offset'] ?? 0;
        $providerName = $this->settings['provider'] ?? 'dataforseo';

        if (!$host) {
            throw new \InvalidArgumentException('Host is required');
        }

        try {
            $provider = BacklinkProviderFactory::make($providerName, $this->settings);

            // Fetch backlinks
            $result = $provider->fetchBacklinks($host, $limit, $offset);

            return [
                'success' => true,
                'host' => $host,
                'provider' => $providerName,
                'backlinks' => $result['items'] ?? [],
                'total' => $result['total'] ?? 0,
                'rows_fetched' => count($result['items'] ?? []),
            ];
        } catch (\Exception $e) {
            Log::error('Backlinks provider driver error', ['error' => $e->getMessage(), 'host' => $host]);
            throw $e;
        }
    }

    public function estimateCost(array $taskPayload): array
    {
        $limit = $taskPayload['limit'] ?? 1000;
        $providerName = $this->settings['provider'] ?? 'dataforseo';

        // Estimate based on provider
        // DataForSEO typically charges per row
        $centsPerRow = match($providerName) {
            'dataforseo' => 0.1, // Example: $0.001 per row = 0.1 cents
            default => 0.1,
        };

        return [
            'units' => (float) $limit,
            'unit_name' => 'rows',
            'cents' => (int) round($limit * $centsPerRow),
        ];
    }
}


