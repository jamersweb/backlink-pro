<?php

namespace App\Services\Billing;

use App\Models\Organization;
use App\Models\UsageEvent;
use App\Services\Billing\UsageRecorder;

class PlanLimiter
{
    /**
     * Get plan limits for organization
     */
    protected function getPlanLimits(Organization $organization): array
    {
        $plan = $organization->plan;
        if ($plan && $plan->limits_json) {
            return $plan->limits_json;
        }

        // Fallback to config
        $planKey = $organization->plan_key ?? 'free';
        return config("plans.{$planKey}", config('plans.free'));
    }

    /**
     * Check if organization can create audit
     */
    public function canCreateAudit(Organization $organization): bool
    {
        $limits = $this->getPlanLimits($organization);
        $auditsPerDay = $limits['audits_per_day'] ?? 10;

        $usageCount = UsageRecorder::getUsageCount(
            $organization,
            UsageEvent::TYPE_AUDIT_CREATED,
            now()->startOfDay(),
            now()->endOfDay()
        );

        return $usageCount < $auditsPerDay;
    }

    /**
     * Get max pages limit
     */
    public function maxPagesLimit(Organization $organization): int
    {
        $limits = $this->getPlanLimits($organization);
        return $limits['pages_limit'] ?? 5;
    }

    /**
     * Get max crawl depth
     */
    public function maxDepth(Organization $organization): int
    {
        $limits = $this->getPlanLimits($organization);
        return $limits['crawl_depth'] ?? 1;
    }

    /**
     * Get max lighthouse pages
     */
    public function maxLighthousePages(Organization $organization): int
    {
        $limits = $this->getPlanLimits($organization);
        return $limits['lighthouse_pages'] ?? 1;
    }

    /**
     * Get max PageSpeed runs per day
     */
    public function maxPageSpeedRunsPerDay(Organization $organization): int
    {
        $limits = $this->getPlanLimits($organization);
        return $limits['pagespeed_runs_per_day'] ?? 2;
    }

    /**
     * Check if organization can run PageSpeed
     */
    public function canRunPageSpeed(Organization $organization): bool
    {
        $limit = $this->maxPageSpeedRunsPerDay($organization);

        $usageCount = UsageRecorder::getUsageCount(
            $organization,
            UsageEvent::TYPE_PAGESPEED_RUN,
            now()->startOfDay(),
            now()->endOfDay()
        );

        return $usageCount < $limit;
    }

    /**
     * Check if can export PDF
     */
    public function canExportPdf(Organization $organization): bool
    {
        $limits = $this->getPlanLimits($organization);
        return $limits['pdf_export'] ?? false;
    }

    /**
     * Check if can use white label
     */
    public function canUseWhiteLabel(Organization $organization): bool
    {
        $limits = $this->getPlanLimits($organization);
        return $limits['white_label'] ?? false;
    }

    /**
     * Check if can add seat
     */
    public function canAddSeat(Organization $organization): bool
    {
        $limits = $this->getPlanLimits($organization);
        $seatsLimit = $limits['seats'] ?? 1;
        return $organization->seats_used < $seatsLimit;
    }

    /**
     * Check if can use custom domain
     */
    public function canUseCustomDomain(Organization $organization): bool
    {
        $limits = $this->getPlanLimits($organization);
        return $limits['custom_domain'] ?? false;
    }

    /**
     * Get seats limit
     */
    public function getSeatsLimit(Organization $organization): int
    {
        $limits = $this->getPlanLimits($organization);
        return $limits['seats'] ?? 1;
    }

    /**
     * Get usage percentage (for warnings)
     */
    public function getUsagePercentage(Organization $organization, string $eventType): float
    {
        $limits = $this->getPlanLimits($organization);
        
        $limitKey = match($eventType) {
            UsageEvent::TYPE_AUDIT_CREATED => 'audits_per_day',
            UsageEvent::TYPE_PAGE_CRAWLED => 'pages_limit',
            UsageEvent::TYPE_LIGHTHOUSE_RUN => 'lighthouse_pages',
            default => null,
        };

        if (!$limitKey || !isset($limits[$limitKey])) {
            return 0;
        }

        $limit = $limits[$limitKey];
        $usage = UsageRecorder::getUsageCount(
            $organization,
            $eventType,
            $organization->usage_period_started_at,
            $organization->usage_period_ends_at
        );

        if ($limit === 0) {
            return 0;
        }

        return min(100, ($usage / $limit) * 100);
    }

    /**
     * Get max keywords tracked
     */
    public function maxKeywords(Organization $organization): int
    {
        $limits = $this->getPlanLimits($organization);
        return $limits['keywords_tracked'] ?? 20;
    }

    /**
     * Get rank check frequency (daily/weekly)
     */
    public function rankCheckFrequency(Organization $organization): string
    {
        $limits = $this->getPlanLimits($organization);
        return $limits['rank_check_frequency'] ?? 'weekly';
    }

    /**
     * Get data retention days for GSC/GA4
     */
    public function dataRetentionDays(Organization $organization): int
    {
        $limits = $this->getPlanLimits($organization);
        return $limits['data_retention_days'] ?? 90;
    }

    /**
     * Check if organization can generate monthly reports
     */
    public function canGenerateMonthlyReport(Organization $organization): bool
    {
        $limits = $this->getPlanLimits($organization);
        return $limits['monthly_report'] ?? false;
    }
}
