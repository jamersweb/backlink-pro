<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ga4DailyMetric extends Model
{
    protected $fillable = [
        'domain_id',
        'date',
        'sessions',
        'total_users',
        'engaged_sessions',
        'engagement_rate',
    ];

    protected $casts = [
        'date' => 'date',
        'sessions' => 'integer',
        'total_users' => 'integer',
        'engaged_sessions' => 'integer',
        'engagement_rate' => 'decimal:4',
    ];

    /**
     * Get the domain
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
