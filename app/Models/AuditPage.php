<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditPage extends Model
{
    protected $fillable = [
        'audit_id',
        'url',
        'status_code',
        'title',
        'title_len',
        'meta_description',
        'meta_len',
        'canonical_url',
        'robots_meta',
        'h1_count',
        'h2_count',
        'h3_count',
        'h4_count',
        'h5_count',
        'h6_count',
        'h1_text',
        'lang',
        'hreflang_present',
        'viewport_present',
        'favicon_present',
        'analytics_tool',
        'iframes_count',
        'flash_used',
        'social_links',
        'x_robots_tag',
        'server_header',
        'x_powered_by',
        'content_type',
        'charset',
        'content_excerpt',
        'word_count',
        'images_total',
        'images_missing_alt',
        'internal_links_count',
        'external_links_count',
        'og_present',
        'twitter_cards_present',
        'schema_types',
        'html_size_bytes',
        'lighthouse_mobile',
        'lighthouse_desktop',
        'performance_metrics',
        'security_headers',
    ];

    protected $casts = [
        'schema_types' => 'array',
        'lighthouse_mobile' => 'array',
        'lighthouse_desktop' => 'array',
        'performance_metrics' => 'array',
        'security_headers' => 'array',
        'social_links' => 'array',
        'og_present' => 'boolean',
        'twitter_cards_present' => 'boolean',
        'hreflang_present' => 'boolean',
        'viewport_present' => 'boolean',
        'favicon_present' => 'boolean',
        'flash_used' => 'boolean',
    ];

    /**
     * Get the audit this page belongs to
     */
    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    /**
     * Get the assets for this page
     */
    public function assets(): HasMany
    {
        return $this->hasMany(AuditAsset::class, 'audit_page_id');
    }
}
