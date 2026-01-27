<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutomationCampaign extends Model
{
    protected $fillable = [
        'user_id',
        'domain_id',
        'name',
        'status',
        'rules_json',
        'totals_json',
        'last_error',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'rules_json' => 'array',
        'totals_json' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_QUEUED = 'queued';
    const STATUS_RUNNING = 'running';
    const STATUS_PAUSED = 'paused';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the domain
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * Get targets
     */
    public function targets(): HasMany
    {
        return $this->hasMany(AutomationTarget::class);
    }

    /**
     * Get jobs
     */
    public function jobs(): HasMany
    {
        return $this->hasMany(AutomationJob::class);
    }

    /**
     * Update totals from jobs
     */
    public function updateTotals(): void
    {
        $this->update([
            'totals_json' => [
                'total' => $this->jobs()->count(),
                'success' => $this->jobs()->where('status', AutomationJob::STATUS_SUCCESS)->count(),
                'failed' => $this->jobs()->where('status', AutomationJob::STATUS_FAILED)->count(),
                'pending' => $this->jobs()->whereIn('status', [AutomationJob::STATUS_QUEUED, AutomationJob::STATUS_LOCKED, AutomationJob::STATUS_RUNNING, AutomationJob::STATUS_RETRYING])->count(),
            ],
        ]);
    }
}
