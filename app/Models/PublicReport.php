<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class PublicReport extends Model
{
    protected $fillable = [
        'domain_id',
        'user_id',
        'token',
        'title',
        'status',
        'expires_at',
        'password_hash',
        'settings_json',
        'snapshot_json',
        'snapshot_generated_at',
    ];

    protected $casts = [
        'settings_json' => 'array',
        'snapshot_json' => 'array',
        'expires_at' => 'datetime',
        'snapshot_generated_at' => 'datetime',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_REVOKED = 'revoked';
    const STATUS_EXPIRED = 'expired';

    /**
     * Get the domain
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all views
     */
    public function views(): HasMany
    {
        return $this->hasMany(PublicReportView::class);
    }

    /**
     * Check if report is expired
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }
        return Carbon::now()->isAfter($this->expires_at);
    }

    /**
     * Check if report is accessible
     */
    public function isAccessible(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }
        if ($this->isExpired()) {
            return false;
        }
        return true;
    }

    /**
     * Check if password is required
     */
    public function requiresPassword(): bool
    {
        return !empty($this->password_hash);
    }

    /**
     * Verify password
     */
    public function verifyPassword(string $password): bool
    {
        if (!$this->password_hash) {
            return true;
        }
        return password_verify($password, $this->password_hash);
    }

    /**
     * Get public URL
     */
    public function getPublicUrl(): string
    {
        return url("/r/{$this->token}");
    }
}
