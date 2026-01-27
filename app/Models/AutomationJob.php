<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutomationJob extends Model
{
    protected $fillable = [
        'campaign_id',
        'target_id',
        'user_id',
        'domain_id',
        'action',
        'status',
        'priority',
        'attempts',
        'max_attempts',
        'lock_token',
        'locked_at',
        'started_at',
        'finished_at',
        'result_json',
        'error_code',
        'error_message',
    ];

    protected $casts = [
        'result_json' => 'array',
        'locked_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    const STATUS_QUEUED = 'queued';
    const STATUS_LOCKED = 'locked';
    const STATUS_RUNNING = 'running';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';
    const STATUS_RETRYING = 'retrying';
    const STATUS_SKIPPED = 'skipped';

    const ACTION_COMMENT = 'comment';
    const ACTION_PROFILE = 'profile';
    const ACTION_FORUM = 'forum';
    const ACTION_GUEST = 'guest';

    const ERROR_CAPTCHA = 'CAPTCHA';
    const ERROR_LOGIN_REQUIRED = 'LOGIN_REQUIRED';
    const ERROR_EMAIL_VERIFICATION_REQUIRED = 'EMAIL_VERIFICATION_REQUIRED';
    const ERROR_ELEMENT_NOT_FOUND = 'ELEMENT_NOT_FOUND';
    const ERROR_TIMEOUT = 'TIMEOUT';
    const ERROR_BLOCKED_BY_CLOUDFLARE = 'BLOCKED_BY_CLOUDFLARE';
    const ERROR_RATE_LIMIT = 'RATE_LIMIT';
    const ERROR_FORM_SUBMIT_FAILED = 'FORM_SUBMIT_FAILED';
    const ERROR_UNKNOWN = 'UNKNOWN';

    /**
     * Get the campaign
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AutomationCampaign::class);
    }

    /**
     * Get the target
     */
    public function target(): BelongsTo
    {
        return $this->belongsTo(AutomationTarget::class);
    }

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

    /**
     * Get logs
     */
    public function logs(): HasMany
    {
        return $this->hasMany(AutomationJobLog::class);
    }

    /**
     * Check if job can be retried
     */
    public function canRetry(): bool
    {
        return $this->status === self::STATUS_FAILED && $this->attempts < $this->max_attempts;
    }
}
