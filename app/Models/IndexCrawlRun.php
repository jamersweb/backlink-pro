<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IndexCrawlRun extends Model
{
    protected $fillable = [
        'domain_id',
        'project_id',
        'user_id',
        'status',
        'started_at',
        'finished_at',
        'total_urls_discovered',
        'total_urls_crawled',
        'total_issues',
        'score',
        'settings_json',
        'summary_json',
        'error_message',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'settings_json' => 'array',
        'summary_json' => 'array',
    ];

    public const STATUS_QUEUED = 'queued';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function urls(): HasMany
    {
        return $this->hasMany(IndexCrawlUrl::class);
    }

    public function sitemaps(): HasMany
    {
        return $this->hasMany(IndexCrawlSitemap::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(IndexCrawlIssue::class);
    }
}
