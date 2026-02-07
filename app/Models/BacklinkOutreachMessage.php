<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BacklinkOutreachMessage extends Model
{
    protected $fillable = [
        'prospect_id',
        'channel',
        'subject',
        'body',
        'status',
    ];

    const CHANNEL_EMAIL = 'email';
    const CHANNEL_CONTACT_FORM = 'contact_form';
    const CHANNEL_LINKEDIN = 'linkedin';
    const CHANNEL_OTHER = 'other';

    const STATUS_DRAFT = 'draft';
    const STATUS_SENT = 'sent';
    const STATUS_REPLIED = 'replied';

    public $timestamps = false;
    protected $dates = ['created_at'];

    /**
     * Get the prospect
     */
    public function prospect(): BelongsTo
    {
        return $this->belongsTo(BacklinkProspect::class);
    }
}
