<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationRule extends Model
{
    protected $fillable = [
        'user_id',
        'domain_id',
        'type',
        'is_enabled',
        'severity',
        'cooldown_minutes',
        'thresholds_json',
        'channels_json',
    ];

    protected $casts = [
        'thresholds_json' => 'array',
        'channels_json' => 'array',
    ];

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the domain
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
