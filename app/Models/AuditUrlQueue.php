<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditUrlQueue extends Model
{
    protected $table = 'audit_url_queue';

    protected $fillable = [
        'audit_id',
        'url',
        'url_normalized',
        'depth',
        'status',
        'discovered_from',
        'last_error',
    ];

    const STATUS_QUEUED = 'queued';
    const STATUS_PROCESSING = 'processing';
    const STATUS_DONE = 'done';
    const STATUS_FAILED = 'failed';

    /**
     * Get the audit this queue item belongs to
     */
    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }
}
