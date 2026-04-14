<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndexCrawlUrl extends Model
{
    protected $fillable = [
        'index_crawl_run_id',
        'domain_id',
        'url',
        'normalized_url',
        'normalized_url_hash',
        'source_found_from',
        'final_url',
        'status_code',
        'content_type',
        'is_html',
        'title',
        'meta_description',
        'canonical_url',
        'meta_robots',
        'x_robots_tag',
        'is_blocked_by_robots',
        'is_noindex',
        'is_in_sitemap',
        'click_depth',
        'internal_inlinks_count',
        'internal_outlinks_count',
        'crawlability_status',
        'indexability_status',
        'issue_flags_json',
        'last_seen_at',
    ];

    protected $casts = [
        'is_html' => 'boolean',
        'is_blocked_by_robots' => 'boolean',
        'is_noindex' => 'boolean',
        'is_in_sitemap' => 'boolean',
        'issue_flags_json' => 'array',
        'last_seen_at' => 'datetime',
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
