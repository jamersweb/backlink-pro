<?php

namespace App\Services\Analytics;

use App\Models\DwCohort;
use App\Models\DwCohortMember;
use App\Models\Organization;
use App\Models\DwEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CohortBuilder
{
    /**
     * Build cohorts for a month
     */
    public function buildCohorts(string $month): void
    {
        $this->buildActivationCohorts($month);
        $this->buildRetentionCohorts($month);
        $this->buildConversionCohorts($month);
    }

    /**
     * Build activation cohorts
     */
    protected function buildActivationCohorts(string $month): void
    {
        $startDate = Carbon::parse($month . '-01')->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Find orgs that signed up this month
        $orgs = Organization::whereBetween('created_at', [$startDate, $endDate])->get();

        $activated = [];
        foreach ($orgs as $org) {
            // Check if activated (first audit completed within 24h OR first PDF/fix within 7 days)
            $firstAudit = DwEvent::where('organization_id', $org->id)
                ->where('event_name', 'audit_completed')
                ->where('event_time', '<=', $org->created_at->addHours(24))
                ->first();

            $firstAction = DwEvent::where('organization_id', $org->id)
                ->whereIn('event_name', ['pdf_exported', 'fix_task_created'])
                ->where('event_time', '<=', $org->created_at->addDays(7))
                ->first();

            if ($firstAudit || $firstAction) {
                $activated[] = $org->id;
                
                DwCohortMember::firstOrCreate([
                    'cohort_month' => $month,
                    'organization_id' => $org->id,
                    'cohort_type' => 'activation',
                ], [
                    'joined_at' => $org->created_at,
                ]);
            }
        }

        // Calculate retention metrics
        $metrics = $this->calculateRetentionMetrics($month, 'activation', $activated);
        
        DwCohort::updateOrCreate(
            [
                'cohort_month' => $month,
                'cohort_type' => 'activation',
            ],
            [
                'size' => count($activated),
                'metrics' => $metrics,
            ]
        );
    }

    /**
     * Build retention cohorts
     */
    protected function buildRetentionCohorts(string $month): void
    {
        $startDate = Carbon::parse($month . '-01')->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $orgs = Organization::whereBetween('created_at', [$startDate, $endDate])->get();
        $orgIds = $orgs->pluck('id')->toArray();

        foreach ($orgIds as $orgId) {
            DwCohortMember::firstOrCreate([
                'cohort_month' => $month,
                'organization_id' => $orgId,
                'cohort_type' => 'retention',
            ], [
                'joined_at' => Organization::find($orgId)->created_at,
            ]);
        }

        $metrics = $this->calculateRetentionMetrics($month, 'retention', $orgIds);
        
        DwCohort::updateOrCreate(
            [
                'cohort_month' => $month,
                'cohort_type' => 'retention',
            ],
            [
                'size' => count($orgIds),
                'metrics' => $metrics,
            ]
        );
    }

    /**
     * Build conversion cohorts
     */
    protected function buildConversionCohorts(string $month): void
    {
        $startDate = Carbon::parse($month . '-01')->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Find free orgs that signed up this month
        $freeOrgs = Organization::where('plan_key', 'free')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $converted = [];
        foreach ($freeOrgs as $org) {
            // Check if upgraded within 30 days
            $upgrade = DwEvent::where('organization_id', $org->id)
                ->where('event_name', 'subscription_upgraded')
                ->where('event_time', '<=', $org->created_at->addDays(30))
                ->first();

            if ($upgrade) {
                $converted[] = $org->id;
            }
        }

        $metrics = [
            'conversion_rate' => count($freeOrgs) > 0 ? (count($converted) / count($freeOrgs)) * 100 : 0,
            'converted_count' => count($converted),
        ];

        DwCohort::updateOrCreate(
            [
                'cohort_month' => $month,
                'cohort_type' => 'conversion',
            ],
            [
                'size' => count($freeOrgs),
                'metrics' => $metrics,
            ]
        );
    }

    /**
     * Calculate retention metrics
     */
    protected function calculateRetentionMetrics(string $month, string $type, array $orgIds): array
    {
        $cohortStart = Carbon::parse($month . '-01')->startOfMonth();
        
        $metrics = [
            'd1' => $this->calculateRetention($orgIds, $cohortStart, 1),
            'd7' => $this->calculateRetention($orgIds, $cohortStart, 7),
            'd14' => $this->calculateRetention($orgIds, $cohortStart, 14),
            'd30' => $this->calculateRetention($orgIds, $cohortStart, 30),
        ];

        return $metrics;
    }

    /**
     * Calculate retention for a period
     */
    protected function calculateRetention(array $orgIds, Carbon $cohortStart, int $days): float
    {
        if (empty($orgIds)) {
            return 0;
        }

        $periodEnd = $cohortStart->copy()->addDays($days);
        
        // Active if created audit OR opened report OR ran rank check
        $active = DwEvent::whereIn('organization_id', $orgIds)
            ->whereIn('event_name', ['audit_created', 'report_viewed', 'rank_check_completed'])
            ->whereBetween('event_time', [$cohortStart, $periodEnd])
            ->distinct('organization_id')
            ->count('organization_id');

        return ($active / count($orgIds)) * 100;
    }
}
