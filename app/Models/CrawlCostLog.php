<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrawlCostLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'domain_id',
        'task_type',
        'provider_code',
        'units',
        'unit_name',
        'estimated_cost_cents',
        'context_json',
        'created_at',
    ];

    protected $casts = [
        'units' => 'decimal:4',
        'estimated_cost_cents' => 'integer',
        'context_json' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

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
