<?php

namespace App\Services\Billing;

use App\Models\Organization;
use App\Models\UsageEvent;

class UsageRecorder
{
    /**
     * Record a usage event
     */
    public static function record(
        int $organizationId,
        string $eventType,
        int $quantity = 1,
        ?int $auditId = null,
        array $metadata = []
    ): UsageEvent {
        return UsageEvent::create([
            'organization_id' => $organizationId,
            'audit_id' => $auditId,
            'event_type' => $eventType,
            'quantity' => $quantity,
            'metadata' => $metadata,
            'occurred_at' => now(),
        ]);
    }

    /**
     * Get usage count for organization in current period
     */
    public static function getUsageCount(
        Organization $organization,
        string $eventType,
        ?\DateTime $startDate = null,
        ?\DateTime $endDate = null
    ): int {
        $query = UsageEvent::where('organization_id', $organization->id)
            ->where('event_type', $eventType);

        // Use organization's usage period if dates not provided
        if ($startDate === null && $organization->usage_period_started_at) {
            $startDate = $organization->usage_period_started_at;
        }
        if ($endDate === null && $organization->usage_period_ends_at) {
            $endDate = $organization->usage_period_ends_at;
        }

        if ($startDate) {
            $query->where('occurred_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('occurred_at', '<=', $endDate);
        }

        return $query->sum('quantity');
    }
}
