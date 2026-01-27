<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'code',
        'price_monthly',
        'limits_json',
        'features_json',
        'is_active',
    ];

    protected $casts = [
        'limits_json' => 'array',
        'features_json' => 'array',
        'price_monthly' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get all subscriptions for this plan
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    /**
     * Get a limit value
     */
    public function getLimit(string $key): ?int
    {
        return $this->limits_json[$key] ?? null;
    }

    /**
     * Get backlink types from features_json
     */
    public function getBacklinkTypes(): array
    {
        // Check if backlink_types is in features_json
        if (isset($this->features_json['backlink_types']) && is_array($this->features_json['backlink_types'])) {
            return $this->features_json['backlink_types'];
        }
        
        // Check if backlink_types is in limits_json (for backward compatibility)
        if (isset($this->limits_json['backlink_types']) && is_array($this->limits_json['backlink_types'])) {
            return $this->limits_json['backlink_types'];
        }
        
        // Default fallback
        return ['comment', 'profile'];
    }

    /**
     * Check if plan allows a specific backlink type
     */
    public function allowsBacklinkType(string $type): bool
    {
        $allowedTypes = $this->getBacklinkTypes();
        return in_array($type, $allowedTypes);
    }

    /**
     * Scope a query to only include active plans
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to order plans
     */
    public function scopeOrdered(Builder $query): Builder
    {
        // If sort_order column exists, use it; otherwise order by name
        if (in_array('sort_order', $this->getConnection()->getSchemaBuilder()->getColumnListing($this->getTable()))) {
            return $query->orderBy('sort_order')->orderBy('name');
        }
        return $query->orderBy('name');
    }
}
