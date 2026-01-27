<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageCounter extends Model
{
    protected $fillable = [
        'user_id',
        'period_type',
        'period_key',
        'metric_key',
        'used',
    ];

    protected $casts = [
        'used' => 'integer',
    ];

    const PERIOD_TYPE_DAY = 'day';
    const PERIOD_TYPE_MONTH = 'month';

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
