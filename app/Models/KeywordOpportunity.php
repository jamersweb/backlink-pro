<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KeywordOpportunity extends Model
{
    protected $fillable = [
        'domain_id',
        'date_range_start',
        'date_range_end',
        'query',
        'page_url',
        'page_hash',
        'impressions',
        'clicks',
        'ctr',
        'position',
        'opportunity_score',
        'status',
    ];

    protected $casts = [
        'date_range_start' => 'date',
        'date_range_end' => 'date',
        'ctr' => 'decimal:4',
        'position' => 'decimal:2',
        'impressions' => 'integer',
        'clicks' => 'integer',
        'opportunity_score' => 'integer',
    ];

    const STATUS_NEW = 'new';
    const STATUS_BRIEF_CREATED = 'brief_created';
    const STATUS_IGNORED = 'ignored';

    /**
     * Get the domain
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
