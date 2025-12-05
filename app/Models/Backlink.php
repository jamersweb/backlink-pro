<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Backlink extends Model
{
    use HasFactory;
    protected $fillable = [
        'campaign_id',
        'backlink_opportunity_id',
        'url',
        'type',
        'keyword',
        'anchor_text',
        'pa',
        'da',
        'status',
        'verified_at',
        'error_message',
        'site_account_id',
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
     * Backlink statuses
     */
    const STATUS_PENDING = 'pending';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_VERIFIED = 'verified';
    const STATUS_FAILED = 'error';

    /**
     * Get the campaign that owns this backlink
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Get the site account used for this backlink
     */
    public function siteAccount(): BelongsTo
    {
        return $this->belongsTo(SiteAccount::class);
    }

    /**
     * Get the backlink opportunity this was created from
     */
    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(BacklinkOpportunity::class, 'backlink_opportunity_id');
    }

    /**
     * Get all logs for this backlink
     */
    public function logs(): HasMany
    {
        return $this->hasMany(Log::class);
    }

    /**
     * Check if backlink is verified
     */
    public function isVerified(): bool
    {
        return $this->status === self::STATUS_VERIFIED && $this->verified_at !== null;
    }

    /**
     * Scope for verified backlinks
     */
    public function scopeVerified($query)
    {
        return $query->where('status', self::STATUS_VERIFIED);
    }

    /**
     * Scope for pending backlinks
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for failed backlinks
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }
}

