<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLink extends Model
{
    protected $fillable = [
        'audit_id',
        'from_url',
        'to_url',
        'to_url_normalized',
        'type',
        'rel_nofollow',
        'anchor_text',
        'status_code',
        'final_url',
        'redirect_hops',
        'is_broken',
        'error',
    ];

    protected $casts = [
        'rel_nofollow' => 'boolean',
        'is_broken' => 'boolean',
    ];

    const TYPE_INTERNAL = 'internal';
    const TYPE_EXTERNAL = 'external';

    /**
     * Get the audit this link belongs to
     */
    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }
}
