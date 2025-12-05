<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'billing_interval', // monthly, yearly
        'max_domains',
        'max_campaigns',
        'daily_backlink_limit',
        'backlink_types', // JSON array of allowed types
        'features', // JSON array of features
        'is_active',
        'sort_order',
        'min_pa',
        'max_pa',
        'min_da',
        'max_da',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'backlink_types' => 'array',
        'features' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Plan slugs
     */
    const PLAN_FREE = 'free';
    const PLAN_STARTER = 'starter';
    const PLAN_PRO = 'pro';
    const PLAN_AGENCY = 'agency';

    /**
     * Get all users subscribed to this plan
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Check if plan allows specific backlink type
     */
    public function allowsBacklinkType(string $type): bool
    {
        return in_array($type, $this->backlink_types ?? []);
    }

    /**
     * Scope for active plans
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope ordered by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}

