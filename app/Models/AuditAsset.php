<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditAsset extends Model
{
    protected $fillable = [
        'audit_id',
        'audit_page_id',
        'page_url',
        'asset_url',
        'type',
        'size_bytes',
        'status_code',
        'content_type',
        'is_third_party',
    ];

    protected $casts = [
        'is_third_party' => 'boolean',
    ];

    const TYPE_IMG = 'img';
    const TYPE_JS = 'js';
    const TYPE_CSS = 'css';
    const TYPE_FONT = 'font';
    const TYPE_OTHER = 'other';

    /**
     * Get the audit this asset belongs to
     */
    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    /**
     * Get the page this asset belongs to
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(AuditPage::class, 'audit_page_id');
    }
}
