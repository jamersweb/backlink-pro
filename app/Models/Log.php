<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Log extends Model
{
    protected $table = 'logs';

    protected $fillable = [
        'backlink_id',
        'status',
        'error_message',
        'timestamp',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

    /**
     * Log statuses
     */
    const STATUS_PENDING = 'pending';
    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR = 'error';

    /**
     * Get the backlink that owns this log
     */
    public function backlink(): BelongsTo
    {
        return $this->belongsTo(Backlink::class);
    }

    /**
     * Scope for error logs
     */
    public function scopeErrors($query)
    {
        return $query->where('status', self::STATUS_ERROR);
    }

    /**
     * Scope for success logs
     */
    public function scopeSuccess($query)
    {
        return $query->where('status', self::STATUS_SUCCESS);
    }
}

