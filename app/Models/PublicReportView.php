<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicReportView extends Model
{
    protected $fillable = [
        'public_report_id',
        'ip_hash',
        'user_agent',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    /**
     * Get the report
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(PublicReport::class, 'public_report_id');
    }
}
