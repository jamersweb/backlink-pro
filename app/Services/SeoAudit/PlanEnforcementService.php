<?php

namespace App\Services\SeoAudit;

use App\Models\Organization;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class PlanEnforcementService
{
    /**
     * Get plan configuration for organization
     */
    public function getPlanConfig(Organization $organization): array
    {
        $planKey = $organization->plan_key ?? 'free';
        return config("plans.{$planKey}", config('plans.free'));
    }

    /**
     * Check if organization can create audit (rate limit)
     */
    public function canCreateAudit(Organization $organization): bool
    {
        $planConfig = $this->getPlanConfig($organization);
        $limit = $planConfig['audits_per_day'] ?? 10;

        $key = "audit:create:org:{$organization->id}";
        
        // Check cache first
        $count = Cache::get($key, 0);
        if ($count >= $limit) {
            return false;
        }

        // Check rate limiter
        return RateLimiter::attempt(
            $key,
            $limit,
            function () {
                // Increment cache counter
                $cacheKey = "audit:create:org:{$organization->id}";
                $current = Cache::get($cacheKey, 0);
                Cache::put($cacheKey, $current + 1, now()->endOfDay());
            },
            86400 // 24 hours
        );
    }

    /**
     * Get plan limits for audit creation
     */
    public function getAuditLimits(Organization $organization): array
    {
        $planConfig = $this->getPlanConfig($organization);
        
        return [
            'pages_limit' => $planConfig['pages_limit'] ?? 5,
            'crawl_depth' => $planConfig['crawl_depth'] ?? 1,
            'lighthouse_pages' => $planConfig['lighthouse_pages'] ?? 1,
            'pdf_export' => $planConfig['pdf_export'] ?? false,
            'white_label' => $planConfig['white_label'] ?? false,
            'custom_domain' => $planConfig['custom_domain'] ?? false,
        ];
    }

    /**
     * Check if organization can use feature
     */
    public function canUseFeature(Organization $organization, string $feature): bool
    {
        $planConfig = $this->getPlanConfig($organization);
        return $planConfig[$feature] ?? false;
    }

    /**
     * Get plan snapshot for audit
     */
    public function getPlanSnapshot(Organization $organization): array
    {
        return [
            'plan_key' => $organization->plan_key ?? 'free',
            'limits' => $this->getAuditLimits($organization),
            'features' => [
                'pdf_export' => $this->canUseFeature($organization, 'pdf_export'),
                'white_label' => $this->canUseFeature($organization, 'white_label'),
                'custom_domain' => $this->canUseFeature($organization, 'custom_domain'),
            ],
        ];
    }

    /**
     * Check IP rate limit for public audits
     */
    public function canCreatePublicAudit(string $ipHash): bool
    {
        return RateLimiter::attempt(
            "audit:create:ip:{$ipHash}",
            20, // 20 audits per hour per IP
            function () {},
            3600 // 1 hour
        );
    }

    /**
     * Record audit creation (increment counter)
     */
    public function recordAuditCreation(Organization $organization): void
    {
        $key = "audit:create:org:{$organization->id}";
        $current = Cache::get($key, 0);
        Cache::put($key, $current + 1, now()->endOfDay());
    }
}
