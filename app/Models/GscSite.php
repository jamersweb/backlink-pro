<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GscSite extends Model
{
    protected $fillable = [
        'organization_id',
        'site_url',
        'permission_level',
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
        return $this->hasMany(GscDailyMetric::class, 'site_url', 'site_url');
    }

    /**
     * Get query metrics
     */
    public function queryMetrics(): HasMany
    {
        return $this->hasMany(GscQueryMetric::class, 'site_url', 'site_url');
    }

    /**
     * Get page metrics
     */
    public function pageMetrics(): HasMany
    {
        return $this->hasMany(GscPageMetric::class, 'site_url', 'site_url');
    }
}
