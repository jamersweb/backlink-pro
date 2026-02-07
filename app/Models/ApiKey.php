<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    protected $fillable = [
        'organization_id',
        'name',
        'key_hash',
        'last_used_at',
        'scopes',
        'is_active',
    ];

    protected $casts = [
        'scopes' => 'array',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($apiKey) {
            if (empty($apiKey->key_hash)) {
                // Generate a key (only shown once)
                $rawKey = 'blp_' . Str::random(48);
                $apiKey->key_hash = Hash::make($rawKey);
                // Store raw key temporarily (will be shown to user once)
                $apiKey->setAttribute('_raw_key', $rawKey);
            }
        });
    }

    /**
     * Get the organization
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Verify API key
     */
    public static function verify(string $rawKey): ?self
    {
        $keys = self::where('is_active', true)->get();
        
        foreach ($keys as $key) {
            if (Hash::check($rawKey, $key->key_hash)) {
                $key->update(['last_used_at' => now()]);
                return $key;
            }
        }

        return null;
    }

    /**
     * Check if key has scope
     */
    public function hasScope(string $scope): bool
    {
        if (empty($this->scopes)) {
            return true; // No scopes = all access
        }

        return in_array($scope, $this->scopes);
    }
}
