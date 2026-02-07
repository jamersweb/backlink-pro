<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditAlert extends Model
{
    protected $fillable = [
        'monitor_id',
        'audit_id',
        'severity',
        'title',
        'message',
        'diff',
        'sent_at',
    ];

    protected $casts = [
        'diff' => 'array',
        'sent_at' => 'datetime',
    ];

    const SEVERITY_INFO = 'info';
    const SEVERITY_WARNING = 'warning';
    const SEVERITY_CRITICAL = 'critical';

    /**
     * Get the monitor
     */
    public function monitor(): BelongsTo
    {
        return $this->belongsTo(AuditMonitor::class);
    }

    /**
     * Get the audit
     */
    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }
}
