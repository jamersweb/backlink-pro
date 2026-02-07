<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetitorSnapshot extends Model
{
    protected $fillable = [
        'competitor_run_id',
        'keyword',
        'competitor_url',
        'domain',
        'title',
        'meta_description',
        'word_count',
        'lighthouse_mobile',
        'lighthouse_desktop',
        'page_weight_bytes',
        'notes',
    ];

    protected $casts = [
        'lighthouse_mobile' => 'array',
        'lighthouse_desktop' => 'array',
        'notes' => 'array',
    ];

    /**
     * Get the competitor run
     */
    public function competitorRun(): BelongsTo
    {
        return $this->belongsTo(CompetitorRun::class);
    }
}
