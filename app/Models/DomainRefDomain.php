<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainRefDomain extends Model
{
    protected $fillable = [
        'run_id',
        'domain',
        'backlinks_count',
        'first_seen',
        'last_seen',
        'tld',
        'country',
        'risk_score',
    ];

    protected $casts = [
        'backlinks_count' => 'integer',
        'first_seen' => 'date',
        'last_seen' => 'date',
        'risk_score' => 'integer',
    ];

    /**
     * Get the run
     */
    public function run(): BelongsTo
    {
        return $this->belongsTo(DomainBacklinkRun::class, 'run_id');
    }
}
