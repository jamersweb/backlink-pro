<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Referral extends Model
{
    protected $fillable = [
        'affiliate_id',
        'referred_user_id',
        'referred_org_id',
        'visitor_id',
        'first_touch_at',
        'last_touch_at',
        'utm',
        'landing_page',
        'referrer_url',
        'status',
    ];

    protected $casts = [
        'utm' => 'array',
        'first_touch_at' => 'datetime',
        'last_touch_at' => 'datetime',
    ];

    const STATUS_CLICKED = 'clicked';
    const STATUS_SIGNED_UP = 'signed_up';
    const STATUS_TRIAL_STARTED = 'trial_started';
    const STATUS_CONVERTED = 'converted';
    const STATUS_CANCELED = 'canceled';

    /**
     * Get the affiliate
     */
    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    /**
     * Get the referred user
     */
    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }

    /**
     * Get the referred organization
     */
    public function referredOrg(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'referred_org_id');
    }

    /**
     * Get commissions
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(AffiliateCommission::class);
    }
}
