<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class TeamInvitation extends Model
{
    protected $fillable = [
        'team_id',
        'email',
        'role',
        'token',
        'status',
        'invited_by_user_id',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REVOKED = 'revoked';
    const STATUS_EXPIRED = 'expired';

    const ROLE_ADMIN = 'admin';
    const ROLE_EDITOR = 'editor';
    const ROLE_VIEWER = 'viewer';

    /**
     * Get the team
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the inviter
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    /**
     * Check if invitation is expired
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }
        return Carbon::now()->isAfter($this->expires_at);
    }

    /**
     * Check if invitation is valid
     */
    public function isValid(): bool
    {
        return $this->status === self::STATUS_PENDING && !$this->isExpired();
    }

    /**
     * Get invite URL
     */
    public function getInviteUrl(): string
    {
        return url("/team/invitations/{$this->token}");
    }
}
