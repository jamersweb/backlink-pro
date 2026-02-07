<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompetitorRun extends Model
{
    protected $fillable = [
        'organization_id',
        'audit_id',
        'keywords',
        'country',
        'status',
    ];

    protected $casts = [
        'keywords' => 'array',
    ];

    const STATUS_QUEUED = 'queued';
    const STATUS_RUNNING = 'running';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    /**
     * Get the organization
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the audit
     */
    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    /**
     * Get snapshots
     */
    public function snapshots(): HasMany
    {
        return $this->hasMany(CompetitorSnapshot::class);
    }
}
