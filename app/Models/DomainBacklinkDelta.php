<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainBacklinkDelta extends Model
{
    protected $fillable = [
        'domain_id',
        'current_run_id',
        'previous_run_id',
        'new_links',
        'lost_links',
        'new_ref_domains',
        'lost_ref_domains',
    ];

    protected $casts = [
        'new_links' => 'integer',
        'lost_links' => 'integer',
        'new_ref_domains' => 'integer',
        'lost_ref_domains' => 'integer',
    ];

    /**
     * Get the domain
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * Get the current run
     */
    public function currentRun(): BelongsTo
    {
        return $this->belongsTo(DomainBacklinkRun::class, 'current_run_id');
    }

    /**
     * Get the previous run
     */
    public function previousRun(): BelongsTo
    {
        return $this->belongsTo(DomainBacklinkRun::class, 'previous_run_id');
    }
}
