<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RankProject extends Model
{
    protected $fillable = [
        'organization_id',
        'name',
        'target_domain',
        'country_code',
        'language_code',
        'status',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_PAUSED = 'paused';

    /**
     * Get the organization
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get keywords for this project
     */
    public function keywords(): HasMany
    {
        return $this->hasMany(RankKeyword::class);
    }

    /**
     * Get active keywords
     */
    public function activeKeywords(): HasMany
    {
        return $this->hasMany(RankKeyword::class)->where('is_active', true);
    }
}
