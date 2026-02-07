<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateCommission extends Model
{
    protected $fillable = [
        'affiliate_id',
        'referral_id',
        'organization_id',
        'stripe_invoice_id',
        'stripe_subscription_id',
        'amount_cents',
        'currency',
        'commission_rate',
        'type',
        'status',
        'eligible_at',
        'paid_at',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'eligible_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    const TYPE_SUBSCRIPTION_FIRST = 'subscription_first';
    const TYPE_SUBSCRIPTION_RECURRING = 'subscription_recurring';
    const TYPE_ONE_TIME = 'one_time';

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_PAID = 'paid';
    const STATUS_REVERSED = 'reversed';

    /**
     * Get the affiliate
     */
    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    /**
     * Get the referral
     */
    public function referral(): BelongsTo
    {
        return $this->belongsTo(Referral::class);
    }

    /**
     * Get the organization
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Check if commission is eligible
     */
    public function isEligible(): bool
    {
        return $this->eligible_at && $this->eligible_at->isPast();
    }
}
