<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SnippetPerformance extends Model
{
    protected $fillable = [
        'domain_id',
        'date',
        'path',
        'avg_load_ms',
        'avg_ttfb_ms',
        'samples',
    ];

    protected $casts = [
        'date' => 'date',
        'avg_load_ms' => 'integer',
        'avg_ttfb_ms' => 'integer',
        'samples' => 'integer',
    ];

    /**
     * Get the domain
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
