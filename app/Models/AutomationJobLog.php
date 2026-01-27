<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationJobLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'job_id',
        'level',
        'message',
        'context_json',
        'created_at',
    ];

    protected $casts = [
        'context_json' => 'array',
        'created_at' => 'datetime',
    ];

    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const LEVEL_DEBUG = 'debug';

    /**
     * Get the job
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(AutomationJob::class);
    }
}
