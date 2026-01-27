<?php

namespace App\Services;

use App\Models\BlockedSite;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BlocklistService
{
    /**
     * Cache duration in seconds (1 hour)
     */
    protected static int $cacheDuration = 3600;

    /**
     * Cache key prefix
     */
    protected static string $cachePrefix = 'blocklist:';

    /**
     * Check if a domain/URL is blocked (with caching)
     */
    public static function isBlocked(string $domainOrUrl): bool
    {
        $normalizedDomain = BlockedSite::normalizeDomain($domainOrUrl);
        $cacheKey = self::$cachePrefix . 'blocked:' . md5($normalizedDomain);

        return Cache::remember($cacheKey, self::$cacheDuration, function () use ($normalizedDomain) {
            return BlockedSite::where('domain', $normalizedDomain)
                ->where('is_active', true)
                ->exists();
        });
    }

    /**
     * Get block reason for a domain (with caching)
     */
    public static function getBlockReason(string $domainOrUrl): ?string
    {
        $normalizedDomain = BlockedSite::normalizeDomain($domainOrUrl);
        $cacheKey = self::$cachePrefix . 'reason:' . md5($normalizedDomain);

        return Cache::remember($cacheKey, self::$cacheDuration, function () use ($normalizedDomain) {
            $blockedSite = BlockedSite::where('domain', $normalizedDomain)
                ->where('is_active', true)
                ->first();
            
            return $blockedSite?->reason;
        });
    }

    /**
     * Block a domain
     */
    public static function blockDomain(
        string $domain,
        ?string $reason = null,
        ?string $blockedBy = null
    ): BlockedSite {
        $normalizedDomain = BlockedSite::normalizeDomain($domain);
        
        $blockedSite = BlockedSite::updateOrCreate(
            ['domain' => $normalizedDomain],
            [
                'reason' => $reason,
                'blocked_by' => $blockedBy ?? 'admin',
                'is_active' => true,
            ]
        );

        // Clear cache for this domain
        self::clearCache($normalizedDomain);

        Log::info('Domain blocked', [
            'domain' => $normalizedDomain,
            'reason' => $reason,
            'blocked_by' => $blockedBy,
        ]);

        return $blockedSite;
    }

    /**
     * Unblock a domain
     */
    public static function unblockDomain(string $domain): bool
    {
        $normalizedDomain = BlockedSite::normalizeDomain($domain);
        $blockedSite = BlockedSite::where('domain', $normalizedDomain)->first();
        
        if ($blockedSite) {
            $blockedSite->update(['is_active' => false]);
            
            // Clear cache for this domain
            self::clearCache($normalizedDomain);

            Log::info('Domain unblocked', ['domain' => $normalizedDomain]);

            return true;
        }
        
        return false;
    }

    /**
     * Clear cache for a specific domain
     */
    public static function clearCache(string $domain): void
    {
        $normalizedDomain = BlockedSite::normalizeDomain($domain);
        Cache::forget(self::$cachePrefix . 'blocked:' . md5($normalizedDomain));
        Cache::forget(self::$cachePrefix . 'reason:' . md5($normalizedDomain));
    }

    /**
     * Clear all blocklist cache
     */
    public static function clearAllCache(): void
    {
        // Get all blocked domains and clear their cache
        BlockedSite::chunk(100, function ($sites) {
            foreach ($sites as $site) {
                self::clearCache($site->domain);
            }
        });
    }

    /**
     * Bulk check multiple domains (optimized)
     */
    public static function bulkCheck(array $domains): array
    {
        $results = [];
        $uncachedDomains = [];

        // Check cache first
        foreach ($domains as $domain) {
            $normalizedDomain = BlockedSite::normalizeDomain($domain);
            $cacheKey = self::$cachePrefix . 'blocked:' . md5($normalizedDomain);
            
            if (Cache::has($cacheKey)) {
                $results[$domain] = Cache::get($cacheKey);
            } else {
                $uncachedDomains[$domain] = $normalizedDomain;
            }
        }

        // Query database for uncached domains
        if (!empty($uncachedDomains)) {
            $blockedDomains = BlockedSite::whereIn('domain', array_values($uncachedDomains))
                ->where('is_active', true)
                ->pluck('domain')
                ->toArray();

            foreach ($uncachedDomains as $original => $normalized) {
                $isBlocked = in_array($normalized, $blockedDomains);
                $results[$original] = $isBlocked;
                
                // Cache the result
                $cacheKey = self::$cachePrefix . 'blocked:' . md5($normalized);
                Cache::put($cacheKey, $isBlocked, self::$cacheDuration);
            }
        }

        return $results;
    }
}

