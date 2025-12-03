<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockedSite extends Model
{
    protected $fillable = [
        'domain',
        'reason',
        'blocked_by',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Check if a domain is blocked
     */
    public static function isBlocked(string $domain): bool
    {
        // Normalize domain (remove protocol, www, trailing slash)
        $normalizedDomain = self::normalizeDomain($domain);
        
        // Check exact match
        if (self::where('domain', $normalizedDomain)
            ->where('is_active', true)
            ->exists()) {
            return true;
        }

        // Check if any parent domain is blocked (e.g., if example.com is blocked, subdomain.example.com should also be blocked)
        $parts = explode('.', $normalizedDomain);
        for ($i = 1; $i < count($parts); $i++) {
            $parentDomain = implode('.', array_slice($parts, $i));
            if (self::where('domain', $parentDomain)
                ->where('is_active', true)
                ->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalize domain for comparison
     */
    public static function normalizeDomain(string $domain): string
    {
        // Remove protocol
        $domain = preg_replace('#^https?://#', '', $domain);
        
        // Remove www.
        $domain = preg_replace('#^www\.#', '', $domain);
        
        // Remove trailing slash and path
        $domain = preg_replace('#/.*$#', '', $domain);
        
        // Remove port
        $domain = preg_replace('#:\d+$#', '', $domain);
        
        // Convert to lowercase
        $domain = strtolower(trim($domain));
        
        return $domain;
    }

    /**
     * Get blocked site by domain
     */
    public static function getByDomain(string $domain): ?self
    {
        $normalizedDomain = self::normalizeDomain($domain);
        return self::where('domain', $normalizedDomain)->first();
    }

    /**
     * Scope for active blocked sites
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
