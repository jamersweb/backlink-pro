<?php

namespace App\Services\Analytics;

use App\Models\Experiment;
use App\Models\ExperimentAssignment;
use App\Models\ExperimentVariant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class ExperimentAssigner
{
    /**
     * Get or assign variant for an experiment
     */
    public function getVariant(string $experimentKey, ?int $organizationId = null, ?int $userId = null, ?string $anonymousId = null): ?ExperimentVariant
    {
        $experiment = Experiment::where('key', $experimentKey)
            ->where('status', 'running')
            ->first();

        if (!$experiment) {
            return null;
        }

        // Check traffic allocation
        if (rand(1, 100) > $experiment->traffic_allocation) {
            return null; // Not in experiment
        }

        // Try to find existing assignment
        $assignment = $this->findAssignment($experiment->id, $organizationId, $userId, $anonymousId);
        
        if ($assignment) {
            return $assignment->variant;
        }

        // Assign new variant
        return $this->assignVariant($experiment, $organizationId, $userId, $anonymousId);
    }

    /**
     * Find existing assignment
     */
    protected function findAssignment(int $experimentId, ?int $organizationId, ?int $userId, ?string $anonymousId): ?ExperimentAssignment
    {
        if ($organizationId) {
            return ExperimentAssignment::where('experiment_id', $experimentId)
                ->where('organization_id', $organizationId)
                ->first();
        }

        if ($userId) {
            return ExperimentAssignment::where('experiment_id', $experimentId)
                ->where('user_id', $userId)
                ->first();
        }

        if ($anonymousId) {
            return ExperimentAssignment::where('experiment_id', $experimentId)
                ->where('anonymous_id', $anonymousId)
                ->first();
        }

        return null;
    }

    /**
     * Assign variant based on weights
     */
    protected function assignVariant(Experiment $experiment, ?int $organizationId, ?int $userId, ?string $anonymousId): ExperimentVariant
    {
        $variants = ExperimentVariant::where('experiment_id', $experiment->id)->get();
        
        // Weighted random selection
        $totalWeight = $variants->sum('weight');
        $random = rand(1, $totalWeight);
        
        $cumulative = 0;
        foreach ($variants as $variant) {
            $cumulative += $variant->weight;
            if ($random <= $cumulative) {
                // Create assignment
                ExperimentAssignment::create([
                    'experiment_id' => $experiment->id,
                    'variant_id' => $variant->id,
                    'organization_id' => $organizationId,
                    'user_id' => $userId,
                    'anonymous_id' => $anonymousId,
                    'assigned_at' => now(),
                ]);

                return $variant;
            }
        }

        // Fallback to first variant
        return $variants->first();
    }

    /**
     * Track experiment event
     */
    public function trackEvent(string $experimentKey, string $eventName, array $meta = []): void
    {
        $experiment = Experiment::where('key', $experimentKey)->first();
        if (!$experiment) {
            return;
        }

        $user = Auth::user();
        $organization = $user?->currentOrganization ?? null;
        $anonymousId = Cookie::get('bp_anon_id');

        $assignment = $this->findAssignment(
            $experiment->id,
            $organization?->id,
            $user?->id,
            $anonymousId
        );

        if (!$assignment) {
            return;
        }

        \App\Models\ExperimentEvent::create([
            'experiment_id' => $experiment->id,
            'variant_id' => $assignment->variant_id,
            'event_name' => $eventName,
            'subject_id' => (string) ($organization?->id ?? $user?->id ?? $anonymousId ?? 'unknown'),
            'occurred_at' => now(),
            'meta' => $meta,
        ]);
    }
}
