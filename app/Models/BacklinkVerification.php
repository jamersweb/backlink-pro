<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BacklinkVerification extends Model
{
    protected $fillable = [
        'campaign_id',
        'target_url',
        'found_on_url',
        'anchor_text',
        'rel_type',
        'first_seen_at',
        'last_seen_at',
        'status',
    ];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    const REL_TYPE_DOFOLLOW = 'dofollow';
    const REL_TYPE_NOFOLLOW = 'nofollow';
    const REL_TYPE_UGC = 'ugc';
    const REL_TYPE_SPONSORED = 'sponsored';

    const STATUS_ACTIVE = 'active';
    const STATUS_LOST = 'lost';

    /**
     * Get the campaign
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(BacklinkCampaign::class);
    }
}
