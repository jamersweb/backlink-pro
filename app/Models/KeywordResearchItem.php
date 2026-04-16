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
        'pattern_type',
        'intent',
        'funnel_stage',
        'cluster_name',
        'recommended_content_type',
        'confidence_score',
        'business_relevance_score',
        'keyword_density_pct',
        'density_status',
        'density_target_url',
        'density_total_words',
        'density_exact_match_count',
        'density_partial_match_count',
        'density_error',
        'density_analyzed_at',
        'keyword_traffic',
        'search_volume',
        'metrics_provider',
        'metrics_status',
        'metrics_error',
        'competition_score',
        'cpc_value',
        'trend_json',
        'provider_response_json',
        'last_enriched_at',
        'ai_reason',
        'generation_meta_json',
        'is_saved',
    ];

    protected $casts = [
        'confidence_score' => 'integer',
        'business_relevance_score' => 'integer',
        'keyword_density_pct' => 'float',
        'keyword_traffic' => 'integer',
        'search_volume' => 'integer',
        'density_total_words' => 'integer',
        'density_exact_match_count' => 'integer',
        'density_partial_match_count' => 'integer',
        'competition_score' => 'integer',
        'cpc_value' => 'float',
        'trend_json' => 'array',
        'provider_response_json' => 'array',
        'generation_meta_json' => 'array',
        'last_enriched_at' => 'datetime',
        'density_analyzed_at' => 'datetime',
        'is_saved' => 'boolean',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(KeywordResearchRun::class, 'run_id');
    }
}
