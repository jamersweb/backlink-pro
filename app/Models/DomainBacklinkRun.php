<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DomainBacklinkRun extends Model
{
    protected $fillable = [
        'domain_id',
        'user_id',
        'status',
        'provider',
        'settings_json',
        'started_at',
        'finished_at',
        'summary_json',
        'totals_json',
        'error_message',
    ];

    protected $casts = [
        'settings_json' => 'array',
        'summary_json' => 'array',
        'totals_json' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    const STATUS_QUEUED = 'queued';
    const STATUS_RUNNING = 'running';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

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
     * Get all backlinks for this run
     */
    public function backlinks(): HasMany
    {
        return $this->hasMany(DomainBacklink::class, 'run_id');
    }

    /**
     * Get all referring domains for this run
     */
    public function refDomains(): HasMany
    {
        return $this->hasMany(DomainRefDomain::class, 'run_id');
    }

    /**
     * Get all anchor summaries for this run
     */
    public function anchorSummaries(): HasMany
    {
        return $this->hasMany(DomainAnchorSummary::class, 'run_id');
    }

    /**
     * Get the delta for this run
     */
    public function delta(): HasOne
    {
        return $this->hasOne(DomainBacklinkDelta::class, 'current_run_id');
    }

    /**
     * Get duration in seconds
     */
    public function getDurationAttribute(): ?int
    {
        if ($this->started_at && $this->finished_at) {
            return $this->finished_at->diffInSeconds($this->started_at);
        }
        return null;
    }
}
