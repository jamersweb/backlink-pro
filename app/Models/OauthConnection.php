<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class OauthConnection extends Model
{
    protected $fillable = [
        'organization_id',
        'provider',
        'account_email',
        'access_token',
        'refresh_token',
        'access_token_encrypted',
        'refresh_token_encrypted',
        'expires_at',
        'scopes',
        'status',
        'last_error',
    ];

    protected $casts = [
        'scopes' => 'array',
        'expires_at' => 'datetime',
    ];

    const PROVIDER_GOOGLE = 'google';

    const STATUS_ACTIVE = 'active';
    const STATUS_REVOKED = 'revoked';
    const STATUS_ERROR = 'error';

    /**
     * Get the organization
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get decrypted access token
     */
    public function getAccessTokenAttribute(): string
    {
        return Crypt::decryptString($this->access_token_encrypted);
    }

    /**
     * Set encrypted access token
     */
    public function setAccessTokenAttribute(string $value): void
    {
        $this->attributes['access_token_encrypted'] = Crypt::encryptString($value);
    }

    /**
     * Get decrypted refresh token
     */
    public function getRefreshTokenAttribute(): string
    {
        return Crypt::decryptString($this->refresh_token_encrypted);
    }

    /**
     * Set encrypted refresh token
     */
    public function setRefreshTokenAttribute(string $value): void
    {
        $this->attributes['refresh_token_encrypted'] = Crypt::encryptString($value);
    }
}
