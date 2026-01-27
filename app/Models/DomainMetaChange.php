<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainMetaChange extends Model
{
    protected $fillable = [
        'domain_id',
        'page_id',
        'user_id',
        'status',
        'meta_before_json',
        'meta_after_json',
        'publish_target',
        'error_message',
    ];

    protected $casts = [
        'meta_before_json' => 'array',
        'meta_after_json' => 'array',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_QUEUED = 'queued';
    const STATUS_PUBLISHED = 'published';
    const STATUS_FAILED = 'failed';

    const PUBLISH_TARGET_CONNECTOR = 'connector';
    const PUBLISH_TARGET_SNIPPET = 'snippet';

    /**
     * Get the domain
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * Get the page
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(DomainMetaPage::class, 'page_id');
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
