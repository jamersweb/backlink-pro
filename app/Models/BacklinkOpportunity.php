<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BacklinkOpportunity extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'backlink_id',
        'site_account_id',
        'url',
        'type',
        'keyword',
        'anchor_text',
        'status',
        'verified_at',
        'error_message',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    /**
     * Backlink types
     */
    const TYPE_COMMENT = 'comment';
    const TYPE_PROFILE = 'profile';
    const TYPE_FORUM = 'forum';
    const TYPE_GUEST = 'guestposting';

    /**
     * Backlink statuses (campaign-specific)
     */
    const STATUS_PENDING = 'pending';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_VERIFIED = 'verified';
    const STATUS_FAILED = 'error';

    /**
     * Get the campaign that owns this opportunity
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Get the backlink from the store
     */
    public function backlink(): BelongsTo
    {
        return $this->belongsTo(Backlink::class, 'backlink_id');
    }

    /**
     * Get the site account used for this opportunity
     */
    public function siteAccount(): BelongsTo
    {
        return $this->belongsTo(SiteAccount::class, 'site_account_id');
    }

    /**
     * Get all logs for this opportunity
     */
    public function logs(): HasMany
    {
        return $this->hasMany(Log::class);
    }

    /**
     * Check if opportunity is verified
     */
    public function isVerified(): bool
    {
        return $this->status === self::STATUS_VERIFIED && $this->verified_at !== null;
    }

    /**
     * Scope for verified opportunities
     */
    public function scopeVerified($query)
    {
        return $query->where('status', self::STATUS_VERIFIED);
    }

    /**
     * Scope for pending opportunities
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for failed opportunities
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope for submitted opportunities
     */
    public function scopeSubmitted($query)
    {
        return $query->where('status', self::STATUS_SUBMITTED);
    }

    /**
     * Get the actual URL (from opportunity or fallback to backlink store)
     */
    public function getActualUrlAttribute(): string
    {
        return $this->url ?? $this->backlink->url ?? '';
    }

    /**
     * Get PA from backlink store (accessor)
     */
    public function getPaAttribute()
    {
        if ($this->relationLoaded('backlink') && $this->backlink) {
            return $this->backlink->pa;
        }
        return $this->backlink()->value('pa');
    }

    /**
     * Get DA from backlink store (accessor)
     */
    public function getDaAttribute()
    {
        if ($this->relationLoaded('backlink') && $this->backlink) {
            return $this->backlink->da;
        }
        return $this->backlink()->value('da');
    }
}
