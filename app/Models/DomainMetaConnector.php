<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class DomainMetaConnector extends Model
{
    protected $fillable = [
        'domain_id',
        'user_id',
        'type',
        'status',
        'base_url',
        'auth_json',
        'last_tested_at',
        'last_error',
    ];

    protected $casts = [
        'last_tested_at' => 'datetime',
    ];

    const TYPE_WORDPRESS = 'wordpress';
    const TYPE_SHOPIFY = 'shopify';
    const TYPE_CUSTOM_JS = 'custom_js';

    const STATUS_CONNECTED = 'connected';
    const STATUS_ERROR = 'error';
    const STATUS_DISCONNECTED = 'disconnected';

    /**
     * Encrypt auth_json when setting
     */
    public function setAuthJsonAttribute($value)
    {
        if ($value === null) {
            $this->attributes['auth_json'] = null;
            return;
        }

        if (is_array($value)) {
            $value = json_encode($value);
        }

        $this->attributes['auth_json'] = Crypt::encryptString($value);
    }

    /**
     * Decrypt auth_json when getting
     */
    public function getAuthJsonAttribute($value)
    {
        if ($value === null) {
            return null;
        }

        try {
            $decrypted = Crypt::decryptString($value);
            $decoded = json_decode($decrypted, true);
            return $decoded !== null ? $decoded : $decrypted;
        } catch (\Exception $e) {
            return null;
        }
    }

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
}
