<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BacklinkAttempt extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'domain_id',
        'campaign_id',
        'job_id',
        'target_url',
        'target_domain',
        'detected_platform',
        'action_attempted',
        'result',
        'failure_reason',
        'created_backlink_url',
        'response_time_ms',
        'metadata_json',
        'created_at',
    ];

    protected $casts = [
        'metadata_json' => 'array',
        'created_at' => 'datetime',
    ];

    const RESULT_SUCCESS = 'success';
    const RESULT_FAILED = 'failed';
    const RESULT_SKIPPED = 'skipped';

    const ACTION_COMMENT = 'comment';
    const ACTION_PROFILE = 'profile';
    const ACTION_FORUM = 'forum';
    const ACTION_GUEST = 'guest';

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
     * Get the campaign
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AutomationCampaign::class);
    }

    /**
     * Get the job
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(AutomationJob::class);
    }

    /**
     * Get page signals
     */
    public function pageSignals(): HasOne
    {
        return $this->hasOne(BacklinkPageSignal::class, 'attempt_id');
    }
}
