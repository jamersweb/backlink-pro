<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BacklinkProspect extends Model
{
    protected $fillable = [
        'campaign_id',
        'prospect_url',
        'domain',
        'type',
        'relevance_score',
        'authority_score',
        'risk_score',
        'contact_email',
        'outreach_status',
        'notes',
    ];

    protected $casts = [
        'relevance_score' => 'integer',
        'authority_score' => 'integer',
        'risk_score' => 'integer',
    ];

    const TYPE_DIRECTORY = 'directory';
    const TYPE_RESOURCE_PAGE = 'resource_page';
    const TYPE_PARTNER = 'partner';
    const TYPE_GUEST = 'guest';
    const TYPE_PRESS = 'press';
    const TYPE_COMMUNITY = 'community';

    const OUTREACH_STATUS_NEW = 'new';
    const OUTREACH_STATUS_CONTACTED = 'contacted';
    const OUTREACH_STATUS_REPLIED = 'replied';
    const OUTREACH_STATUS_NEGOTIATING = 'negotiating';
    const OUTREACH_STATUS_WON = 'won';
    const OUTREACH_STATUS_LOST = 'lost';

    /**
     * Get the campaign
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(BacklinkCampaign::class);
    }

    /**
     * Get outreach messages for this prospect
     */
    public function outreachMessages(): HasMany
    {
        return $this->hasMany(BacklinkOutreachMessage::class);
    }
}
