<?php

namespace App\Services\Crawl\Drivers;

use App\Models\GscTopQuery;
use Illuminate\Support\Facades\DB;

class GscPositionFallbackDriver implements CrawlDriverInterface
{
    protected array $settings;
    protected ?int $domainId;

    public function __construct(array $settings = [])
    {
        $this->settings = $settings;
        $this->domainId = $settings['domain_id'] ?? null;
    }

    /**
     * Check if driver supports a task type
     */
    public function supports(string $taskType): bool
    {
        return $taskType === 'serp.rank_check';
    }

    /**
     * Validate provider settings
     */
    public function validateSettings(array $settings): array
    {
        // GSC fallback doesn't need settings - uses existing GSC data
        return ['ok' => true, 'message' => 'GSC fallback driver ready'];
    }

    /**
     * Execute rank check
     * 
     * @param array $taskPayload ['keyword' => string, 'location_code' => string|null, 'device' => string]
     */
    public function execute(array $taskPayload): array
    {
        $keyword = $taskPayload['keyword'] ?? '';
        $locationCode = $taskPayload['location_code'] ?? null;
        $device = $taskPayload['device'] ?? 'desktop';

        if (!$keyword) {
            return [
                'success' => false,
                'error' => 'Keyword is required',
            ];
        }

        // Get average position from GSC data (last 28 days)
        $endDate = now();
        $startDate = $endDate->copy()->subDays(28);

        if (!$this->domainId) {
            return [
                'success' => false,
                'error' => 'Domain ID is required in settings',
            ];
        }

        $query = GscTopQuery::where('domain_id', $this->domainId)
            ->where('query', $keyword)
            ->whereBetween('date', [$startDate, $endDate]);

        // Note: GSC data doesn't always have device/location filtering in our current structure
        // This is an approximation - use all data available
        $results = $query->select([
                DB::raw('AVG(position) as avg_position'),
                DB::raw('SUM(clicks) as total_clicks'),
                DB::raw('SUM(impressions) as total_impressions'),
            ])
            ->first();

        if (!$results || !$results->avg_position) {
            return [
                'success' => true,
                'position' => null,
                'found_url' => null,
                'matched' => false,
                'note' => 'No GSC data available for this keyword',
            ];
        }

        $position = (int)round($results->avg_position);

        // Check if position is in top 100 (GSC only shows up to ~100)
        if ($position > 100) {
            $position = null;
        }

        // Try to find matching URL from GSC top pages (if available)
        $foundUrl = null;
        $matched = false;

        // Domain matching would require domain URL from settings if needed
        // For MVP, just return position

        // Note: We'd need query+page data from GSC to find exact URL
        // For MVP, just return position
        // In future, can enhance with query+page dimension data

        return [
            'success' => true,
            'position' => $position,
            'found_url' => $foundUrl,
            'matched' => $matched,
            'note' => 'Based on Google Search Console average position (approximation)',
            'source' => 'gsc_fallback',
        ];
    }

    /**
     * Estimate cost (free for GSC fallback)
     */
    public function estimateCost(array $taskPayload): array
    {
        return [
            'units' => 1,
            'unit_name' => 'keywords',
            'cents' => 0, // Free
        ];
    }
}

