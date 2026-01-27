<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DomainMetaPage extends Model
{
    protected $fillable = [
        'domain_id',
        'source',
        'url',
        'path',
        'external_id',
        'resource_type',
        'title_current',
        'meta_current_json',
        'meta_published_json',
    ];

    protected $casts = [
        'meta_current_json' => 'array',
        'meta_published_json' => 'array',
    ];

    const SOURCE_MANUAL = 'manual';
    const SOURCE_AUDIT = 'audit';
    const SOURCE_GSC = 'gsc';
    const SOURCE_CONNECTOR = 'connector';

    const RESOURCE_TYPE_WP_PAGE = 'wp_page';
    const RESOURCE_TYPE_WP_POST = 'wp_post';
    const RESOURCE_TYPE_SHOPIFY_PRODUCT = 'shopify_product';
    const RESOURCE_TYPE_SHOPIFY_PAGE = 'shopify_page';
    const RESOURCE_TYPE_SHOPIFY_COLLECTION = 'shopify_collection';
    const RESOURCE_TYPE_SHOPIFY_ARTICLE = 'shopify_article';
    const RESOURCE_TYPE_CUSTOM = 'custom';

    /**
     * Get the domain
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * Get all changes for this page
     */
    public function changes(): HasMany
    {
        return $this->hasMany(DomainMetaChange::class, 'page_id');
    }

    /**
     * Get latest draft change
     */
    public function latestDraft()
    {
        return $this->changes()
            ->where('status', DomainMetaChange::STATUS_DRAFT)
            ->latest()
            ->first();
    }

    /**
     * Get latest published change
     */
    public function latestPublished()
    {
        return $this->changes()
            ->where('status', DomainMetaChange::STATUS_PUBLISHED)
            ->latest()
            ->first();
    }
}
