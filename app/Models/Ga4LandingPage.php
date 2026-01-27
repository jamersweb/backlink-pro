<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ga4LandingPage extends Model
{
    protected $fillable = [
        'domain_id',
        'date',
        'landing_page',
        'sessions',
        'total_users',
    ];

    protected $casts = [
        'date' => 'date',
        'sessions' => 'integer',
        'total_users' => 'integer',
    ];

    /**
     * Get the domain
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
