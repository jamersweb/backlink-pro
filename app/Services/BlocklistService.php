<?php

namespace App\Services;

use App\Models\BlockedSite;
use Illuminate\Support\Facades\Log;

class BlocklistService
{
    /**
     * Check if a domain/URL is blocked
     */
    public static function isBlocked(string $domainOrUrl): bool
    {
        return BlockedSite::isBlocked($domainOrUrl);
    }

    /**
     * Get block reason for a domain
     */
    public static function getBlockReason(string $domainOrUrl): ?string
    {
        $normalizedDomain = BlockedSite::normalizeDomain($domainOrUrl);
        $blockedSite = BlockedSite::getByDomain($normalizedDomain);
        
        return $blockedSite?->reason;
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
        
        return BlockedSite::updateOrCreate(
            ['domain' => $normalizedDomain],
            [
                'reason' => $reason,
                'blocked_by' => $blockedBy ?? 'admin',
                'is_active' => true,
            ]
        );
    }

    /**
     * Unblock a domain
     */
    public static function unblockDomain(string $domain): bool
    {
        $normalizedDomain = BlockedSite::normalizeDomain($domain);
        $blockedSite = BlockedSite::getByDomain($normalizedDomain);
        
        if ($blockedSite) {
            return $blockedSite->update(['is_active' => false]);
        }
        
        return false;
    }
}


