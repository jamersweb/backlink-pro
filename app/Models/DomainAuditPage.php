<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DomainAuditPage extends Model
{
    protected $fillable = [
        'domain_audit_id',
        'url',
        'path',
        'status_code',
        'final_url',
        'response_time_ms',
        'content_type',
        'title',
        'meta_description',
        'canonical',
        'robots_meta',
        'h1_count',
        'word_count',
        'is_indexable',
        'issues_count',
    ];

    protected $casts = [
        'status_code' => 'integer',
        'response_time_ms' => 'integer',
        'h1_count' => 'integer',
        'word_count' => 'integer',
        'is_indexable' => 'boolean',
        'issues_count' => 'integer',
    ];

    /**
     * Get the audit this page belongs to
     */
    public function audit(): BelongsTo
    {
        return $this->belongsTo(DomainAudit::class, 'domain_audit_id');
    }

    /**
     * Get all issues for this page
     */
    public function issues(): HasMany
    {
        return $this->hasMany(DomainAuditIssue::class);
    }
}
