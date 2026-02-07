<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageSpeedResult extends Model
{
    protected $fillable = [
        'organization_id',
        'url',
        'strategy',
        'fetched_at',
        'expires_at',
        'status',
        'http_status',
        'error_message',
        'payload',
        'kpis',
    ];

    protected $casts = [
        'fetched_at' => 'datetime',
        'expires_at' => 'datetime',
        'payload' => 'array',
        'kpis' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
