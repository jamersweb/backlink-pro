<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ga4DailyMetric extends Model
{
    protected $fillable = [
        'organization_id',
        'property_id',
        'date',
        'sessions',
        'users',
        'new_users',
        'engagement_rate',
        'avg_engagement_time_sec',
        'page_views',
        'conversions',
        'revenue',
    ];

    protected $casts = [
        'date' => 'date',
        'sessions' => 'integer',
        'users' => 'integer',
        'new_users' => 'integer',
        'engagement_rate' => 'decimal:4',
        'avg_engagement_time_sec' => 'integer',
        'page_views' => 'integer',
        'conversions' => 'integer',
        'revenue' => 'decimal:2',
    ];

    /**
     * Get the organization
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
