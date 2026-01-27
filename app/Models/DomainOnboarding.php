<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainOnboarding extends Model
{
    protected $fillable = [
        'domain_id',
        'user_id',
        'status',
        'current_step',
        'steps_json',
    ];

    protected $casts = [
        'steps_json' => 'array',
    ];

    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';

    const STEP_DOMAIN_ADDED = 'domain_added';
    const STEP_AUDIT_STARTED = 'audit_started';
    const STEP_AUDIT_COMPLETED = 'audit_completed';
    const STEP_GOOGLE_CONNECTED = 'google_connected';
    const STEP_GOOGLE_SELECTED = 'google_selected';
    const STEP_BACKLINKS_STARTED = 'backlinks_started';
    const STEP_BACKLINKS_COMPLETED = 'backlinks_completed';
    const STEP_META_CONNECTOR = 'meta_connector';
    const STEP_INSIGHTS_GENERATED = 'insights_generated';
    const STEP_REPORT_CREATED = 'report_created';

    /**
     * Get the domain
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark step as done
     */
    public function markStepDone(string $step, array $data = []): void
    {
        $steps = $this->steps_json ?? [];
        $steps[$step] = array_merge([
            'done' => true,
            'at' => now()->toIso8601String(),
        ], $data);
        $this->update(['steps_json' => $steps]);
    }

    /**
     * Check if step is done
     */
    public function isStepDone(string $step): bool
    {
        return ($this->steps_json[$step]['done'] ?? false) === true;
    }

    /**
     * Get step data
     */
    public function getStepData(string $step): ?array
    {
        return $this->steps_json[$step] ?? null;
    }
}
