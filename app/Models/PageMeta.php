<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageMeta extends Model
{
    protected $fillable = [
        'page_key',
        'page_name',
        'route_name',
        'url_path',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_title',
        'og_description',
        'og_image',
        'og_type',
        'twitter_card',
        'twitter_title',
        'twitter_description',
        'twitter_image',
        'schema_json',
        'content_json',
        'is_active',
        'is_indexable',
        'is_followable',
        'canonical_url',
        'updated_by',
    ];

    protected $casts = [
        'schema_json' => 'array',
        'content_json' => 'array',
        'is_active' => 'boolean',
        'is_indexable' => 'boolean',
        'is_followable' => 'boolean',
    ];

    /**
     * Get the user who last updated this page meta
     */
    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get page meta by key
     */
    public static function getByKey(string $key): ?self
    {
        return static::where('page_key', $key)->first();
    }

    /**
     * Get all meta data formatted for frontend
     */
    public function getFormattedMeta(): array
    {
        return [
            'title' => $this->meta_title,
            'description' => $this->meta_description,
            'keywords' => $this->meta_keywords,
            'og' => [
                'title' => $this->og_title ?? $this->meta_title,
                'description' => $this->og_description ?? $this->meta_description,
                'image' => $this->og_image,
                'type' => $this->og_type,
            ],
            'twitter' => [
                'card' => $this->twitter_card,
                'title' => $this->twitter_title ?? $this->og_title ?? $this->meta_title,
                'description' => $this->twitter_description ?? $this->og_description ?? $this->meta_description,
                'image' => $this->twitter_image ?? $this->og_image,
            ],
            'robots' => $this->getRobotsDirective(),
            'canonical' => $this->canonical_url,
            'schema' => $this->schema_json,
        ];
    }

    /**
     * Get robots meta directive
     */
    public function getRobotsDirective(): string
    {
        $directives = [];
        $directives[] = $this->is_indexable ? 'index' : 'noindex';
        $directives[] = $this->is_followable ? 'follow' : 'nofollow';
        return implode(', ', $directives);
    }

    /**
     * Scope for active pages
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get default schema templates
     */
    public static function getSchemaTemplates(): array
    {
        return [
            'organization' => [
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                'name' => 'BacklinkPro',
                'url' => config('app.url'),
                'logo' => config('app.url') . '/images/logo.png',
                'description' => '',
                'sameAs' => [],
            ],
            'website' => [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => 'BacklinkPro',
                'url' => config('app.url'),
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => config('app.url') . '/search?q={search_term_string}',
                    'query-input' => 'required name=search_term_string',
                ],
            ],
            'webpage' => [
                '@context' => 'https://schema.org',
                '@type' => 'WebPage',
                'name' => '',
                'description' => '',
                'url' => '',
            ],
            'product' => [
                '@context' => 'https://schema.org',
                '@type' => 'Product',
                'name' => '',
                'description' => '',
                'brand' => [
                    '@type' => 'Brand',
                    'name' => 'BacklinkPro',
                ],
                'offers' => [
                    '@type' => 'AggregateOffer',
                    'priceCurrency' => 'USD',
                    'lowPrice' => '',
                    'highPrice' => '',
                ],
            ],
            'faq' => [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => [],
            ],
            'article' => [
                '@context' => 'https://schema.org',
                '@type' => 'Article',
                'headline' => '',
                'description' => '',
                'author' => [
                    '@type' => 'Organization',
                    'name' => 'BacklinkPro',
                ],
                'publisher' => [
                    '@type' => 'Organization',
                    'name' => 'BacklinkPro',
                    'logo' => [
                        '@type' => 'ImageObject',
                        'url' => config('app.url') . '/images/logo.png',
                    ],
                ],
                'datePublished' => '',
                'dateModified' => '',
            ],
            'breadcrumb' => [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => [],
            ],
            'software' => [
                '@context' => 'https://schema.org',
                '@type' => 'SoftwareApplication',
                'name' => 'BacklinkPro',
                'applicationCategory' => 'BusinessApplication',
                'operatingSystem' => 'Web',
                'offers' => [
                    '@type' => 'Offer',
                    'price' => '',
                    'priceCurrency' => 'USD',
                ],
            ],
        ];
    }
}
