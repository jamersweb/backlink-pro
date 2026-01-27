<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainKpiSnapshot extends Model
{
    protected $fillable = [
        'domain_id',
        'date',
        'seo_health_score',
        'gsc_clicks_28d',
        'gsc_impressions_28d',
        'ga_sessions_28d',
        'backlinks_new',
        'backlinks_lost',
        'meta_failed_count',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Get the domain
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
