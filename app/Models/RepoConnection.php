<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class RepoConnection extends Model
{
    protected $fillable = [
        'organization_id',
        'provider',
        'account_name',
        'access_token_encrypted',
    ];

    const PROVIDER_GITHUB = 'github';
    const PROVIDER_GITLAB = 'gitlab';

    /**
     * Get the organization
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get repos for this connection
     */
    public function repos(): HasMany
    {
        return $this->hasMany(Repo::class);
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
}
