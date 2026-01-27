<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainInsightRun extends Model
{
    protected $fillable = [
        'domain_id',
        'user_id',
        'status',
        'period_days',
        'started_at',
        'finished_at',
        'summary_json',
        'score_json',
        'error_message',
    ];

    protected $casts = [
        'summary_json' => 'array',
        'score_json' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    const STATUS_QUEUED = 'queued';
    const STATUS_RUNNING = 'running';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    /**
     * Get the domain
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
