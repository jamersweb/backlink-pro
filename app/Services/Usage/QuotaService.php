<?php

namespace App\Services\Usage;

use App\Models\User;
use App\Models\UserSubscription;
use App\Models\UsageCounter;
use App\Models\UsageEvent;
use App\Models\Plan;
use App\Models\Domain;
use App\Exceptions\QuotaExceededException;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class QuotaService
{
    /**
     * Get or create subscription for user
     */
    public function getSubscription(User $user): UserSubscription
    {
        $subscription = $user->subscription;

        if (!$subscription) {
            // Create default subscription (starter plan)
            $starterPlan = Plan::where('code', 'starter')->first();
            if (!$starterPlan) {
                throw new \Exception('Starter plan not found. Please run PlanSeeder.');
            }

            $now = Carbon::now();
            $subscription = UserSubscription::create([
                'user_id' => $user->id,
                'plan_id' => $starterPlan->id,
                'status' => UserSubscription::STATUS_ACTIVE,
                'started_at' => $now,
                'current_period_start' => $now->copy()->startOfMonth(),
                'current_period_end' => $now->copy()->addMonth()->startOfMonth(),
            ]);
        }

        return $subscription;
    }

    /**
     * Get limit for a quota key
     */
    public function getLimit(User $user, string $quotaKey): ?int
    {
        $subscription = $this->getSubscription($user);
        if (!$subscription->isActive()) {
            return null;
        }

        $plan = $subscription->plan;
        return $plan->getLimit($quotaKey);
    }

    /**
     * Get used amount for a metric
     */
    public function getUsed(User $user, string $metricKey, string $periodType): int
    {
        $periodKey = $this->getPeriodKey($periodType);

        $counter = UsageCounter::where('user_id', $user->id)
            ->where('period_type', $periodType)
            ->where('period_key', $periodKey)
            ->where('metric_key', $metricKey)
            ->first();

        return $counter ? $counter->used : 0;
    }

    /**
     * Assert user can perform action
     */
    public function assertCan(User $user, string $quotaKey, int $amount = 1, array $context = []): void
    {
        $limit = $this->getLimit($user, $quotaKey);

        // No limit means unlimited
        if ($limit === null || $limit < 0) {
            return;
        }

        // Determine period type and metric key
        [$periodType, $metricKey] = $this->parseQuotaKey($quotaKey);

        // Special handling for absolute caps
        if ($quotaKey === 'domains.max_active') {
            $used = Domain::where('user_id', $user->id)
                ->where('status', Domain::STATUS_ACTIVE)
                ->count();
        } else {
            $used = $this->getUsed($user, $metricKey, $periodType);
        }

        if ($used + $amount > $limit) {
            // Determine when the quota will reset
            if ($periodType === 'day') {
                $resetDate = Carbon::tomorrow();
            } else {
                $subscription = $this->getSubscription($user);
                $resetDate = $subscription->getResetDate();
            }

            throw new QuotaExceededException($quotaKey, $limit, $used, $resetDate);
        }
    }

    /**
     * Consume quota
     */
    public function consume(User $user, string $metricKey, int $amount = 1, string $periodType = 'month', array $context = []): void
    {
        $periodKey = $this->getPeriodKey($periodType);

        DB::transaction(function () use ($user, $metricKey, $amount, $periodType, $periodKey, $context) {
            // Upsert and increment atomically
            UsageCounter::updateOrInsert(
                [
                    'user_id' => $user->id,
                    'period_type' => $periodType,
                    'period_key' => $periodKey,
                    'metric_key' => $metricKey,
                ],
                [
                    'used' => DB::raw("COALESCE(used, 0) + {$amount}"),
                    'updated_at' => now(),
                ]
            );

            // Record event for auditability
            UsageEvent::create([
                'user_id' => $user->id,
                'domain_id' => $context['domain_id'] ?? null,
                'metric_key' => $metricKey,
                'amount' => $amount,
                'context_json' => $context,
            ]);
        });
    }

    /**
     * Parse quota key to determine period type and metric key
     */
    protected function parseQuotaKey(string $quotaKey): array
    {
        // Determine period type based on suffix; metric key must match quota key
        if (str_ends_with($quotaKey, '_per_day')) {
            $periodType = 'day';
        } elseif (str_ends_with($quotaKey, '_per_month')) {
            $periodType = 'month';
        } else {
            // Default to monthly for keys without an explicit suffix
            $periodType = 'month';
        }

        // Store and read counters using the full quota key
        $metricKey = $quotaKey;

        return [$periodType, $metricKey];
    }

    /**
     * Get period key for current period
     */
    protected function getPeriodKey(string $periodType): string
    {
        if ($periodType === 'day') {
            return Carbon::now()->toDateString();
        }
        return Carbon::now()->format('Y-m');
    }
}

