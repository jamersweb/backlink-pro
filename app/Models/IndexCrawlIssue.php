<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndexCrawlIssue extends Model
{
    protected $fillable = [
        'index_crawl_run_id',
        'domain_id',
        'issue_key',
        'issue_name',
        'severity',
        'affected_urls_count',
        'description',
        'recommendation',
        'metadata_json',
    ];

    protected $casts = [
        'metadata_json' => 'array',
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
