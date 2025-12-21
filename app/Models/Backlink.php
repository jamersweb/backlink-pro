<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Backlink extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'url',
        'pa',
        'da',
        'site_type',
        'status',
        'daily_site_limit',
        'metadata',
    ];

    protected $casts = [
        'pa' => 'integer',
        'da' => 'integer',
        'daily_site_limit' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Backlink statuses (for the global store)
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_BANNED = 'banned';

    /**
     * Site types
     */
    const TYPE_COMMENT = 'comment';
    const TYPE_PROFILE = 'profile';
    const TYPE_FORUM = 'forum';
    const TYPE_GUEST = 'guestposting';
    const TYPE_OTHER = 'other';

    /**
     * Get categories for this backlink (many-to-many)
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'backlink_category')
            ->withTimestamps();
    }

    /**
     * Get opportunities that use this backlink (campaign-specific)
     */
    public function opportunities(): HasMany
    {
        return $this->hasMany(BacklinkOpportunity::class, 'backlink_id');
    }

    /**
     * Scope for active backlinks
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for inactive backlinks
     */
    public function scopeInactive($query)
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }

    /**
     * Scope for banned backlinks
     */
    public function scopeBanned($query)
    {
        return $query->where('status', self::STATUS_BANNED);
    }

    /**
     * Scope filtered by PA range
     */
    public function scopePaRange($query, $minPa, $maxPa)
    {
        return $query->whereBetween('pa', [$minPa, $maxPa]);
    }

    /**
     * Scope filtered by DA range
     */
    public function scopeDaRange($query, $minDa, $maxDa)
    {
        return $query->whereBetween('da', [$minDa, $maxDa]);
    }

    /**
     * Scope filtered by site type
     */
    public function scopeSiteType($query, $siteType)
    {
        return $query->where('site_type', $siteType);
    }
}
