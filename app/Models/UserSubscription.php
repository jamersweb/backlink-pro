<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class UserSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'plan_id',
        'status',
        'started_at',
        'current_period_start',
        'current_period_end',
        'meta_json',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'current_period_start' => 'date',
        'current_period_end' => 'date',
        'meta_json' => 'array',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_PAUSED = 'paused';
    const STATUS_CANCELED = 'canceled';

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the plan
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Check if subscription is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Get reset date (next period start)
     */
    public function getResetDate(): Carbon
    {
        return Carbon::parse($this->current_period_end);
    }
}
