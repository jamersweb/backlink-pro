<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KeywordResearchItem extends Model
{
    protected $fillable = [
        'run_id',
        'keyword',
        'normalized_keyword',
        'source',
        'intent',
        'funnel_stage',
        'cluster_name',
        'recommended_content_type',
        'confidence_score',
        'business_relevance_score',
        'keyword_density_pct',
        'keyword_traffic',
        'ai_reason',
        'is_saved',
    ];

    protected $casts = [
        'confidence_score' => 'integer',
        'business_relevance_score' => 'integer',
        'keyword_density_pct' => 'float',
        'keyword_traffic' => 'integer',
        'is_saved' => 'boolean',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(KeywordResearchRun::class, 'run_id');
    }
}
