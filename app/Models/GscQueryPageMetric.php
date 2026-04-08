<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GscQueryPageMetric extends Model
{
    protected $fillable = [
        'domain_id',
        'site_url',
        'date',
        'query',
        'page_url',
        'clicks',
        'impressions',
        'ctr',
        'position',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
