<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrawlProvider extends Model
{
    protected $fillable = [
        'name',
        'code',
        'category',
        'is_active',
        'settings_schema_json',
        'cost_model_json',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings_schema_json' => 'array',
        'cost_model_json' => 'array',
    ];

    /**
     * Get user settings for this provider
     */
    public function userSettings(): HasMany
    {
        return $this->hasMany(UserProviderSetting::class, 'provider_code', 'code');
    }

    /**
     * Get domain preferences using this provider
     */
    public function domainPreferences(): HasMany
    {
        return $this->hasMany(DomainProviderPreference::class, 'provider_code', 'code');
    }
}
