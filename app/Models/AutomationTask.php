<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationTask extends Model
{
    protected $fillable = [
        'campaign_id',
        'type',
        'status',
        'payload',
        'result',
        'error_message',
        'locked_at',
        'locked_by',
        'started_at',
        'completed_at',
        'retry_count',
        'max_retries',
    ];

    protected $casts = [
        'payload' => 'array',
        'result' => 'array',
        'locked_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'retry_count' => 'integer',
        'max_retries' => 'integer',
    ];

    /**
     * Task types
     */
    const TYPE_COMMENT = 'comment';
    const TYPE_PROFILE = 'profile';
    const TYPE_FORUM = 'forum';
    const TYPE_GUEST = 'guest';
    const TYPE_EMAIL_CONFIRMATION_CLICK = 'email_confirmation_click';

    /**
     * Task statuses
     */
    const STATUS_PENDING = 'pending';
    const STATUS_RUNNING = 'running';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the campaign this task belongs to
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Lock the task (prevent duplicate processing)
     */
    public function lock(string $workerId): bool
    {
        if ($this->isLocked()) {
            return false;
        }

        return $this->update([
            'status' => self::STATUS_RUNNING,
            'locked_at' => now(),
            'locked_by' => $workerId,
            'started_at' => now(),
        ]);
    }

    /**
     * Unlock the task
     */
    public function unlock(): bool
    {
        return $this->update([
            'locked_at' => null,
            'locked_by' => null,
        ]);
    }

    /**
     * Check if task is locked
     */
    public function isLocked(): bool
    {
        return $this->locked_at !== null &&
               $this->locked_at->addMinutes(30)->isFuture(); // 30 min lock timeout
    }

    /**
     * Mark task as completed
     */
    public function markCompleted(array $result = []): bool
    {
        return $this->update([
            'status' => self::STATUS_SUCCESS,
            'result' => $result,
            'completed_at' => now(),
            'locked_at' => null,
            'locked_by' => null,
        ]);
    }

    /**
     * Mark task as failed
     */
    public function markFailed(string $errorMessage): bool
    {
        // Truncate error message if too long (database text column can handle it, but keep reasonable)
        if (strlen($errorMessage) > 5000) {
            $errorMessage = substr($errorMessage, 0, 4997) . '...';
        }

        $this->increment('retry_count');

        $status = ($this->retry_count >= $this->max_retries)
            ? self::STATUS_FAILED
            : self::STATUS_PENDING;

        $updateData = [
            'status' => $status,
            'error_message' => $errorMessage,
            'locked_at' => null,
            'locked_by' => null,
        ];

        // If retrying, clear started_at so it can be picked up again
        if ($status === self::STATUS_PENDING) {
            $updateData['started_at'] = null;
        }

        return $this->update($updateData);
    }

    /**
     * Scope for pending tasks
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where(function ($q) {
                $q->whereNull('locked_at')
                  ->orWhere('locked_at', '<', now()->subMinutes(30)); // Expired locks
            });
    }

    /**
     * Scope for failed tasks
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope for tasks by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}

