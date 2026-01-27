<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobFailure extends Model
{
    protected $fillable = [
        'feature',
        'job_name',
        'domain_id',
        'user_id',
        'run_ref',
        'exception_class',
        'exception_message',
        'failed_at',
        'context_json',
    ];

    protected $casts = [
        'context_json' => 'array',
        'failed_at' => 'datetime',
    ];

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
