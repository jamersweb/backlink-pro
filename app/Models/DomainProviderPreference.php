<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainProviderPreference extends Model
{
    protected $fillable = [
        'domain_id',
        'task_type',
        'provider_code',
        'fallback_codes_json',
    ];

    protected $casts = [
        'fallback_codes_json' => 'array',
    ];

    /**
     * Get the domain
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * Get the provider
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(CrawlProvider::class, 'provider_code', 'code');
    }
}
