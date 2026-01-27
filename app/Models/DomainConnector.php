<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class DomainConnector extends Model
{
    protected $fillable = [
        'domain_id',
        'type',
        'status',
        'credentials_json',
        'settings_json',
        'last_tested_at',
        'last_error_code',
        'last_error_message',
    ];

    protected $casts = [
        'settings_json' => 'array',
        'last_tested_at' => 'datetime',
    ];

    const TYPE_WP = 'wp';
    const TYPE_SHOPIFY = 'shopify';
    const TYPE_GENERIC = 'generic';
    const TYPE_CUSTOM_JS = 'custom_js';

    const STATUS_CONNECTED = 'connected';
    const STATUS_DISCONNECTED = 'disconnected';
    const STATUS_ERROR = 'error';

    /**
     * Encrypt credentials_json when setting
     */
    public function setCredentialsJsonAttribute($value)
    {
        if ($value === null) {
            $this->attributes['credentials_json'] = null;
            return;
        }

        if (is_array($value)) {
            $value = json_encode($value);
        }

        $this->attributes['credentials_json'] = Crypt::encryptString($value);
    }

    /**
     * Decrypt credentials_json when getting
     */
    public function getCredentialsJsonAttribute($value)
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
}
