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
        'tagline',
        'price_monthly',
        'price_annual',
        'limits_json',
        'display_limits',
        'features_json',
        'includes',
        'is_active',
        'is_public',
        'is_highlighted',
        'badge',
        'sort_order',
        'cta_primary_label',
        'cta_primary_href',
        'cta_secondary_label',
        'cta_secondary_href',
    ];

    protected $casts = [
        'limits_json' => 'array',
        'display_limits' => 'array',
        'features_json' => 'array',
        'includes' => 'array',
        'price_monthly' => 'integer',
        'price_annual' => 'integer',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'is_highlighted' => 'boolean',
        'sort_order' => 'integer',
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
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Scope a query to only include public plans (for pricing page)
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    /**
     * Get monthly price formatted as dollars
     */
    public function getMonthlyPriceAttribute(): ?float
    {
        return $this->price_monthly ? $this->price_monthly / 100 : null;
    }

    /**
     * Get annual price formatted as dollars (per month)
     */
    public function getAnnualPriceAttribute(): ?float
    {
        return $this->price_annual ? $this->price_annual / 100 : null;
    }

    /**
     * Format plan for marketing/pricing page display
     */
    public function toMarketingArray(): array
    {
        return [
            'id' => $this->code,
            'name' => $this->name,
            'tagline' => $this->tagline,
            'highlight' => $this->is_highlighted,
            'badge' => $this->badge,
            'prices' => [
                'monthly' => [
                    'amount' => $this->monthly_price ?? 0,
                    'suffix' => '/mo',
                ],
                'annual' => [
                    'amount' => $this->annual_price ?? ($this->monthly_price ? round($this->monthly_price * 0.85) : 0),
                    'suffix' => '/mo billed annually',
                ],
            ],
            'limits' => $this->display_limits ?? [],
            'includes' => $this->includes ?? [],
            'ctas' => [
                'primary' => [
                    'label' => $this->cta_primary_label ?? 'Get Started',
                    'href' => $this->cta_primary_href ?? '/register',
                ],
                'secondary' => $this->cta_secondary_label ? [
                    'label' => $this->cta_secondary_label,
                    'href' => $this->cta_secondary_href ?? '#',
                ] : null,
            ],
        ];
    }

    /**
     * Get feature matrix value for a feature key
     */
    public function getFeatureMatrixValue(string $key): mixed
    {
        // Check features_json first
        if (isset($this->features_json[$key])) {
            return $this->features_json[$key];
        }
        
        // Check limits_json for numeric values
        if (isset($this->limits_json[$key])) {
            return $this->limits_json[$key];
        }
        
        return false;
    }
}
