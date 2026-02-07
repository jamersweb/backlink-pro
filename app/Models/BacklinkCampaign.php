<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BacklinkCampaign extends Model
{
    protected $fillable = [
        'organization_id',
        'audit_id',
        'name',
        'target_domain',
        'strategy_version',
        'status',
        'goals',
    ];

    protected $casts = [
        'goals' => 'array',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_ACTIVE = 'active';
    const STATUS_PAUSED = 'paused';
    const STATUS_COMPLETED = 'completed';

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
     * Get prospects for this campaign
     */
    public function prospects(): HasMany
    {
        return $this->hasMany(BacklinkProspect::class);
    }

    /**
     * Get verifications for this campaign
     */
    public function verifications(): HasMany
    {
        return $this->hasMany(BacklinkVerification::class);
    }
}
