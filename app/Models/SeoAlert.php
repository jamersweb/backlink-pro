<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoAlert extends Model
{
    protected $fillable = [
        'organization_id',
        'rule_id',
        'severity',
        'title',
        'message',
        'diff',
        'related_date',
        'sent_at',
    ];

    protected $casts = [
        'diff' => 'array',
        'related_date' => 'date',
        'sent_at' => 'datetime',
    ];

    const SEVERITY_INFO = 'info';
    const SEVERITY_WARNING = 'warning';
    const SEVERITY_CRITICAL = 'critical';

    /**
     * Get the organization
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the rule
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(SeoAlertRule::class);
    }
}
