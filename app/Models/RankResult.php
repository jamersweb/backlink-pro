<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RankResult extends Model
{
    protected $fillable = [
        'rank_check_id',
        'domain_id',
        'rank_keyword_id',
        'keyword',
        'position',
        'found_url',
        'matched',
        'serp_top_urls_json',
        'features_json',
        'fetched_at',
    ];

    protected $casts = [
        'fetched_at' => 'datetime',
        'serp_top_urls_json' => 'array',
        'features_json' => 'array',
    ];

    /**
     * Get the rank check
     */
    public function rankCheck(): BelongsTo
    {
        return $this->belongsTo(RankCheck::class);
    }

    /**
     * Get the domain
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * Get the keyword
     */
    public function rankKeyword(): BelongsTo
    {
        return $this->belongsTo(RankKeyword::class);
    }
}
