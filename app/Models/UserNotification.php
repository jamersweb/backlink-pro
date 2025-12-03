<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UserNotification extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'subject_type',
        'subject_id',
        'data',
        'read',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read' => 'boolean',
        'read_at' => 'datetime',
    ];

    /**
     * Notification types
     */
    const TYPE_SUCCESS = 'success';
    const TYPE_ERROR = 'error';
    const TYPE_WARNING = 'warning';
    const TYPE_INFO = 'info';

    /**
     * Get the user that owns this notification
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the subject of the notification (polymorphic)
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(): void
    {
        if (!$this->read) {
            $this->update([
                'read' => true,
                'read_at' => now(),
            ]);
        }
    }

    /**
     * Scope for unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->where('read', false);
    }

    /**
     * Scope for read notifications
     */
    public function scopeRead($query)
    {
        return $query->where('read', true);
    }

    /**
     * Scope for specific type
     */
    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Create a notification
     */
    public static function createNotification(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?Model $subject = null,
        ?array $data = null
    ): self {
        return self::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->id,
            'data' => $data,
        ]);
    }
}
