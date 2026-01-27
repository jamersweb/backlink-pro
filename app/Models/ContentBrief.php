<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentBrief extends Model
{
    protected $fillable = [
        'domain_id',
        'user_id',
        'title',
        'primary_keyword',
        'secondary_keywords_json',
        'target_type',
        'target_url',
        'suggested_slug',
        'intent',
        'outline_json',
        'faq_json',
        'internal_links_json',
        'meta_suggestion_json',
        'status',
    ];

    protected $casts = [
        'secondary_keywords_json' => 'array',
        'outline_json' => 'array',
        'faq_json' => 'array',
        'internal_links_json' => 'array',
        'meta_suggestion_json' => 'array',
    ];

    const TARGET_TYPE_EXISTING_PAGE = 'existing_page';
    const TARGET_TYPE_NEW_PAGE = 'new_page';

    const INTENT_INFORMATIONAL = 'informational';
    const INTENT_TRANSACTIONAL = 'transactional';
    const INTENT_NAVIGATIONAL = 'navigational';
    const INTENT_MIXED = 'mixed';

    const STATUS_DRAFT = 'draft';
    const STATUS_WRITING = 'writing';
    const STATUS_PUBLISHED = 'published';
    const STATUS_ARCHIVED = 'archived';

    /**
     * Get the domain
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
