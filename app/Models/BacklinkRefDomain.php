<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BacklinkRefDomain extends Model
{
    protected $fillable = [
        'domain_id',
        'ref_domain',
        'first_seen_at',
        'last_seen_at',
        'links_count',
        'follow_links_count',
        'metrics_json',
        'quality_score',
        'risk_score',
        'status',
        'notes',
    ];

    protected $casts = [
        'metrics_json' => 'array',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    const STATUS_OK = 'ok';
    const STATUS_REVIEW = 'review';
    const STATUS_TOXIC = 'toxic';
    const STATUS_DISAVOWED = 'disavowed';

    /**
     * Get the domain
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * Get backlinks from this referring domain
     */
    public function backlinks(): HasMany
    {
        return $this->hasMany(DomainBacklink::class, 'ref_domain_id');
    }
}
