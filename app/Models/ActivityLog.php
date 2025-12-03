<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'properties',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    /**
     * Activity action types
     */
    const ACTION_CAMPAIGN_CREATED = 'campaign.created';
    const ACTION_CAMPAIGN_UPDATED = 'campaign.updated';
    const ACTION_CAMPAIGN_DELETED = 'campaign.deleted';
    const ACTION_CAMPAIGN_PAUSED = 'campaign.paused';
    const ACTION_CAMPAIGN_RESUMED = 'campaign.resumed';
    
    const ACTION_BACKLINK_CREATED = 'backlink.created';
    const ACTION_BACKLINK_VERIFIED = 'backlink.verified';
    const ACTION_BACKLINK_FAILED = 'backlink.failed';
    
    const ACTION_DOMAIN_CREATED = 'domain.created';
    const ACTION_DOMAIN_UPDATED = 'domain.updated';
    const ACTION_DOMAIN_DELETED = 'domain.deleted';
    
    const ACTION_USER_LOGIN = 'user.login';
    const ACTION_USER_LOGOUT = 'user.logout';
    const ACTION_USER_REGISTERED = 'user.registered';
    const ACTION_USER_UPDATED = 'user.updated';
    
    const ACTION_ADMIN_USER_UPDATED = 'admin.user.updated';
    const ACTION_ADMIN_PLAN_CREATED = 'admin.plan.created';
    const ACTION_ADMIN_PLAN_UPDATED = 'admin.plan.updated';
    const ACTION_ADMIN_PLAN_DELETED = 'admin.plan.deleted';

    /**
     * Get the user that performed the action
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the subject of the activity (polymorphic)
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope for specific action
     */
    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope for specific user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for specific subject
     */
    public function scopeForSubject($query, string $type, int $id)
    {
        return $query->where('subject_type', $type)->where('subject_id', $id);
    }

    /**
     * Log an activity
     */
    public static function log(
        string $action,
        ?Model $subject = null,
        ?string $description = null,
        ?array $properties = null,
        ?int $userId = null
    ): self {
        return self::create([
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->id,
            'description' => $description,
            'properties' => $properties,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
