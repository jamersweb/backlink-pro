<?php

namespace App\Services;

use App\Models\BacklinkOpportunity;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RateLimitingService
{
    /**
     * Check if domain has reached rate limit (max 1 backlink per site per day)
     */
    public static function checkDomainRateLimit(string $url, int $campaignId): bool
    {
        $domain = self::extractDomain($url);

        if (!$domain) {
            return false;
        }

        // Check if we've created a backlink opportunity for this domain today for this campaign
        $todayBacklinks = BacklinkOpportunity::where('campaign_id', $campaignId)
            ->whereDate('created_at', today())
            ->get()
            ->filter(function($opportunity) use ($domain) {
                $backlinkDomain = self::extractDomain($opportunity->url);
                return $backlinkDomain === $domain;
            })
            ->count();

        if ($todayBacklinks >= 1) {
            Log::info('Domain rate limit reached', [
                'domain' => $domain,
                'campaign_id' => $campaignId,
                'today_count' => $todayBacklinks,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Extract domain from URL
     */
    public static function extractDomain(string $url): ?string
    {
        $parsed = parse_url($url);
        if (!isset($parsed['host'])) {
            return null;
        }

        $host = $parsed['host'];

        // Remove www.
        $host = preg_replace('/^www\./', '', $host);

        // Convert to lowercase
        $host = strtolower($host);

        return $host;
    }

    /**
     * Check API rate limit using Redis/Cache
     */
    public static function checkApiRateLimit(string $identifier, int $maxRequests = 100, int $windowMinutes = 60): bool
    {
        $key = "api_rate_limit:{$identifier}";
        $current = Cache::get($key, 0);

        if ($current >= $maxRequests) {
            Log::warning('API rate limit exceeded', [
                'identifier' => $identifier,
                'current' => $current,
                'max' => $maxRequests,
            ]);
            return false;
        }

        Cache::put($key, $current + 1, now()->addMinutes($windowMinutes));
        return true;
    }

    /**
     * Check Gmail API rate limit
     */
    public static function checkGmailApiRateLimit(int $userId, int $maxRequests = 250, int $windowSeconds = 100): bool
    {
        $key = "gmail_api_rate_limit:user:{$userId}";
        $current = Cache::get($key, 0);

        if ($current >= $maxRequests) {
            Log::warning('Gmail API rate limit exceeded', [
                'user_id' => $userId,
                'current' => $current,
                'max' => $maxRequests,
            ]);
            return false;
        }

        // Increment counter
        Cache::put($key, $current + 1, now()->addSeconds($windowSeconds));
        return true;
    }

    /**
     * Get remaining API requests
     */
    public static function getRemainingApiRequests(string $identifier, int $maxRequests = 100): int
    {
        $key = "api_rate_limit:{$identifier}";
        $current = Cache::get($key, 0);
        return max(0, $maxRequests - $current);
    }

    /**
     * Get remaining Gmail API requests
     */
    public static function getRemainingGmailRequests(int $userId, int $maxRequests = 250): int
    {
        $key = "gmail_api_rate_limit:user:{$userId}";
        $current = Cache::get($key, 0);
        return max(0, $maxRequests - $current);
    }
}


