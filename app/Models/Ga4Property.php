<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ga4Property extends Model
{
    protected $fillable = [
        'organization_id',
        'property_id',
        'display_name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the organization
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get daily metrics
     */
    public function dailyMetrics(): HasMany
    {
        return $this->hasMany(Ga4DailyMetric::class, 'property_id', 'property_id');
    }

    /**
     * Get page metrics
     */
    public function pageMetrics(): HasMany
    {
        return $this->hasMany(Ga4PageMetric::class, 'property_id', 'property_id');
    }
}
