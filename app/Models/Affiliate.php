<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Affiliate extends Model
{
    protected $fillable = [
        'user_id',
        'organization_id',
        'code',
        'status',
        'payout_method',
        'payout_details',
    ];

    protected $casts = [
        'payout_details' => 'array',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_SUSPENDED = 'suspended';

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($affiliate) {
            if (empty($affiliate->code)) {
                $affiliate->code = strtoupper(Str::random(8));
            }
        });
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the organization
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get referrals
     */
    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class);
    }

    /**
     * Get commissions
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(AffiliateCommission::class);
    }

    /**
     * Get payouts
     */
    public function payouts(): HasMany
    {
        return $this->hasMany(AffiliatePayout::class);
    }

    /**
     * Get total earnings (approved + paid commissions)
     */
    public function getTotalEarningsAttribute(): int
    {
        return $this->commissions()
            ->whereIn('status', ['approved', 'paid'])
            ->sum('amount_cents');
    }

    /**
     * Get pending earnings
     */
    public function getPendingEarningsAttribute(): int
    {
        return $this->commissions()
            ->where('status', 'pending')
            ->where('eligible_at', '<=', now())
            ->sum('amount_cents');
    }
}
