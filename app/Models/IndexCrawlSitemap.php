<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndexCrawlSitemap extends Model
{
    protected $fillable = [
        'index_crawl_run_id',
        'domain_id',
        'sitemap_url',
        'type',
        'fetch_status',
        'total_urls_found',
        'valid_urls_found',
        'health_score',
        'issues_json',
        'fetched_at',
    ];

    protected $casts = [
        'issues_json' => 'array',
        'fetched_at' => 'datetime',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(IndexCrawlRun::class, 'index_crawl_run_id');
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
