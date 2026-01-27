<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DomainAudit extends Model
{
    protected $fillable = [
        'domain_id',
        'user_id',
        'status',
        'settings_json',
        'started_at',
        'finished_at',
        'health_score',
        'summary_json',
        'error_message',
    ];

    protected $casts = [
        'settings_json' => 'array',
        'summary_json' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'health_score' => 'integer',
    ];

    const STATUS_QUEUED = 'queued';
    const STATUS_RUNNING = 'running';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    /**
     * Get the domain this audit belongs to
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * Get the user that owns this audit
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all pages for this audit
     */
    public function pages(): HasMany
    {
        return $this->hasMany(DomainAuditPage::class);
    }

    /**
     * Get all issues for this audit
     */
    public function issues(): HasMany
    {
        return $this->hasMany(DomainAuditIssue::class);
    }

    /**
     * Get all metrics for this audit
     */
    public function metrics(): HasMany
    {
        return $this->hasMany(DomainAuditMetric::class);
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
