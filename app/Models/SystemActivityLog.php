<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'domain_id',
        'feature',
        'action',
        'status',
        'message',
        'context_json',
    ];

    protected $casts = [
        'context_json' => 'array',
    ];

    const FEATURE_DOMAINS = 'domains';
    const FEATURE_AUDITS = 'audits';
    const FEATURE_GOOGLE = 'google';
    const FEATURE_BACKLINKS = 'backlinks';
    const FEATURE_META = 'meta';
    const FEATURE_INSIGHTS = 'insights';
    const FEATURE_PLANS = 'plans';
    const FEATURE_SYSTEM = 'system';

    const STATUS_INFO = 'info';
    const STATUS_SUCCESS = 'success';
    const STATUS_WARNING = 'warning';
    const STATUS_ERROR = 'error';

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
