<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CruxResult extends Model
{
    protected $fillable = [
        'organization_id',
        'target_type',
        'target_value',
        'form_factor',
        'fetched_at',
        'expires_at',
        'status',
        'error_message',
        'raw_payload',
        'kpis',
    ];

    protected $casts = [
        'fetched_at' => 'datetime',
        'expires_at' => 'datetime',
        'raw_payload' => 'array',
        'kpis' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
