<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ga4PageMetric extends Model
{
    protected $fillable = [
        'organization_id',
        'property_id',
        'date',
        'page_path',
        'page_title',
        'views',
        'active_users',
        'conversions',
    ];

    protected $casts = [
        'date' => 'date',
        'views' => 'integer',
        'active_users' => 'integer',
        'conversions' => 'integer',
    ];

    /**
     * Get the organization
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
