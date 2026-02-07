<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ServiceCatalog extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'base_price_cents',
        'currency',
        'pricing_model',
        'includes',
        'estimated_days',
        'is_active',
    ];

    protected $casts = [
        'includes' => 'array',
        'is_active' => 'boolean',
    ];

    const PRICING_FIXED = 'fixed';
    const PRICING_PER_PAGE = 'per_page';
    const PRICING_TIERED = 'tiered';
    const PRICING_CUSTOM_QUOTE = 'custom_quote';

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($service) {
            if (empty($service->slug)) {
                $service->slug = Str::slug($service->name);
            }
        });
    }

    /**
     * Get service request items
     */
    public function serviceRequestItems(): HasMany
    {
        return $this->hasMany(ServiceRequestItem::class);
    }

    /**
     * Calculate price for service request
     */
    public function calculatePrice(array $meta = []): int
    {
        return match($this->pricing_model) {
            self::PRICING_FIXED => $this->base_price_cents,
            self::PRICING_PER_PAGE => $this->base_price_cents * ($meta['page_count'] ?? 1),
            self::PRICING_TIERED => $this->calculateTieredPrice($meta),
            default => $this->base_price_cents, // Custom quote handled separately
        };
    }

    /**
     * Calculate tiered pricing
     */
    protected function calculateTieredPrice(array $meta): int
    {
        $count = $meta['affected_count'] ?? 1;
        
        // Example tiers: 1-5 = base, 6-10 = base*1.5, 11+ = base*2
        if ($count <= 5) {
            return $this->base_price_cents;
        } elseif ($count <= 10) {
            return (int) ($this->base_price_cents * 1.5);
        } else {
            return $this->base_price_cents * 2;
        }
    }
}
