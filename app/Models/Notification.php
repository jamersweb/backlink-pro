<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'domain_id',
        'type',
        'title',
        'message',
        'severity',
        'action_url',
        'evidence_json',
        'fingerprint',
        'status',
        'muted',
        'snoozed_until',
    ];

    protected $casts = [
        'evidence_json' => 'array',
        'snoozed_until' => 'datetime',
    ];

    const STATUS_UNREAD = 'unread';
    const STATUS_READ = 'read';
    const STATUS_ARCHIVED = 'archived';

    const SEVERITY_INFO = 'info';
    const SEVERITY_WARNING = 'warning';
    const SEVERITY_CRITICAL = 'critical';

    // Alert types
    const TYPE_RANK_DROP = 'rank_drop';
    const TYPE_TOXIC_SPIKE = 'toxic_spike';
    const TYPE_AUDIT_CRITICAL = 'audit_critical';
    const TYPE_GOOGLE_DISCONNECT = 'google_disconnect';
    const TYPE_BACKLINKS_LOST_SPIKE = 'backlinks_lost_spike';
    const TYPE_META_PUBLISH_FAILED = 'meta_publish_failed';
    const TYPE_QUOTA_LIMIT = 'quota_limit';

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the domain
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
