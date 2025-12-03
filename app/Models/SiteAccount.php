<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SiteAccount extends Model
{
    protected $fillable = [
        'user_id',
        'campaign_id',
        'site_domain',
        'login_email',
        'username',
        'password', // Encrypted if stored
        'status',
        'verification_link',
        'email_verification_status',
        'last_verification_check_at',
    ];

    protected $casts = [
        'last_verification_check_at' => 'datetime',
    ];

    /**
     * Account statuses
     */
    const STATUS_CREATED = 'created';
    const STATUS_WAITING_EMAIL = 'waiting_email';
    const STATUS_VERIFIED = 'verified';
    const STATUS_FAILED = 'failed';

    /**
     * Email verification statuses
     */
    const EMAIL_STATUS_PENDING = 'pending';
    const EMAIL_STATUS_FOUND = 'found';
    const EMAIL_STATUS_TIMEOUT = 'timeout';

    /**
     * Get the user that owns this site account
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the campaign this site account belongs to
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Get all backlinks created with this site account
     */
    public function backlinks(): HasMany
    {
        return $this->hasMany(Backlink::class);
    }

    /**
     * Check if account is verified
     */
    public function isVerified(): bool
    {
        return $this->status === self::STATUS_VERIFIED;
    }

    /**
     * Scope for verified accounts
     */
    public function scopeVerified($query)
    {
        return $query->where('status', self::STATUS_VERIFIED);
    }

    /**
     * Scope for accounts waiting for email verification
     */
    public function scopeWaitingEmail($query)
    {
        return $query->where('status', self::STATUS_WAITING_EMAIL);
    }

    /**
     * Scope for accounts on a specific domain
     */
    public function scopeForDomain($query, string $domain)
    {
        return $query->where('site_domain', $domain);
    }
}

