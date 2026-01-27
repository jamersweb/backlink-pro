<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class UserProviderSetting extends Model
{
    protected $fillable = [
        'user_id',
        'provider_code',
        'settings_json',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the provider
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(CrawlProvider::class, 'provider_code', 'code');
    }

    /**
     * Encrypt settings when setting
     */
    public function setSettingsJsonAttribute($value)
    {
        $this->attributes['settings_json'] = $value ? Crypt::encryptString(json_encode($value)) : null;
    }

    /**
     * Decrypt settings when getting
     */
    public function getSettingsJsonAttribute($value)
    {
        if (!$value) {
            return null;
        }
        try {
            return json_decode(Crypt::decryptString($value), true);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if provider is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->settings_json);
    }
}
