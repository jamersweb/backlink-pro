<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditSnapshot extends Model
{
    protected $fillable = [
        'audit_id',
        'snapshot',
    ];

    protected $casts = [
        'snapshot' => 'array',
    ];

    /**
     * Get the audit
     */
    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }
}
