<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaptchaLog extends Model
{
    protected $fillable = [
        'campaign_id',
        'site_domain',
        'captcha_type',
        'service', // 2captcha, anticaptcha
        'order_id',
        'status',
        'error',
        'estimated_cost',
        'solved_at',
    ];

    protected $casts = [
        'estimated_cost' => 'decimal:4',
        'solved_at' => 'datetime',
    ];

    /**
     * Captcha types
     */
    const TYPE_IMAGE = 'image';
    const TYPE_RECAPTCHA_V2 = 'recaptcha_v2';
    const TYPE_RECAPTCHA_INVISIBLE = 'recaptcha_invisible';
    const TYPE_HCAPTCHA = 'hcaptcha';

    /**
     * Services
     */
    const SERVICE_2CAPTCHA = '2captcha';
    const SERVICE_ANTICAPTCHA = 'anticaptcha';

    /**
     * Statuses
     */
    const STATUS_PENDING = 'pending';
    const STATUS_SOLVED = 'solved';
    const STATUS_FAILED = 'failed';

    /**
     * Get the campaign this captcha log belongs to
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Scope for solved captchas
     */
    public function scopeSolved($query)
    {
        return $query->where('status', self::STATUS_SOLVED);
    }

    /**
     * Scope for failed captchas
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Get total cost for a campaign
     */
    public static function getTotalCostForCampaign(int $campaignId): float
    {
        return self::where('campaign_id', $campaignId)
            ->where('status', self::STATUS_SOLVED)
            ->sum('estimated_cost');
    }
}

