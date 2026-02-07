<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeoAlertRule extends Model
{
    protected $fillable = [
        'organization_id',
        'name',
        'type',
        'config',
        'is_enabled',
        'notify_emails',
    ];

    protected $casts = [
        'config' => 'array',
        'notify_emails' => 'array',
        'is_enabled' => 'boolean',
    ];

    const TYPE_RANK_DROP = 'rank_drop';
    const TYPE_GSC_CLICKS_DROP = 'gsc_clicks_drop';
    const TYPE_GA4_SESSIONS_DROP = 'ga4_sessions_drop';
    const TYPE_CONVERSION_DROP = 'conversion_drop';

    /**
     * Get the organization
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get alerts for this rule
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(SeoAlert::class);
    }
}
