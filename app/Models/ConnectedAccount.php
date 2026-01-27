<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class ConnectedAccount extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'provider',
        'service',
        'email',
        'provider_user_id',
        'access_token',
        'refresh_token',
        'expires_at',
        'scopes',
        'status',
    ];

    protected $casts = [
        'scopes' => 'array',
        'expires_at' => 'datetime',
    ];

    /**
     * Providers
     */
    const PROVIDER_GOOGLE = 'google';

    /**
     * Services
     */
    const SERVICE_GMAIL = 'gmail';
    const SERVICE_SEO = 'seo';

    /**
     * Statuses
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_REVOKED = 'revoked';
    const STATUS_ERROR = 'error';
    const STATUS_EXPIRED = 'expired';

    /**
     * Get the user that owns this connected account
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all campaigns using this connected account
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'gmail_account_id');
    }

    /**
     * Set access token (encrypted)
     */
    public function setAccessTokenAttribute($value)
    {
        $this->attributes['access_token'] = ($value && $value !== '') ? Crypt::encryptString($value) : null;
    }

    /**
     * Get access token (decrypted)
     */
    public function getAccessTokenAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    /**
     * Set refresh token (encrypted)
     */
    public function setRefreshTokenAttribute($value)
    {
        $this->attributes['refresh_token'] = ($value && $value !== '') ? Crypt::encryptString($value) : null;
    }

    /**
     * Get refresh token (decrypted)
     */
    public function getRefreshTokenAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    /**
     * Check if token is expired or expiring soon
     */
    public function isExpiredOrExpiringSoon(int $minutesBefore = 60): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return $this->expires_at->isPast() || 
               $this->expires_at->subMinutes($minutesBefore)->isPast();
    }

    /**
     * Check if account is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE && !$this->isExpiredOrExpiringSoon();
    }

    /**
     * Scope for active accounts
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('expires_at', '>', now());
    }

    /**
     * Scope for Google accounts
     */
    public function scopeGoogle($query)
    {
        return $query->where('provider', self::PROVIDER_GOOGLE);
    }

    /**
     * Scope for service
     */
    public function scopeService($query, string $service)
    {
        return $query->where('service', $service);
    }
}

