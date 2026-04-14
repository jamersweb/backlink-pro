<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ga4SourceMetric extends Model
{
    protected $fillable = [
        'organization_id',
        'property_id',
        'date',
        'source_medium',
        'sessions',
        'active_users',
    ];

    protected $casts = [
        'date' => 'date',
        'sessions' => 'integer',
        'active_users' => 'integer',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}

