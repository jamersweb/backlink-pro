<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditIssue extends Model
{
    protected $fillable = [
        'audit_id',
        'audit_run_id',
        'url',
        'module_key',
        'issue_type',
        'severity',
        'status',
        'message',
        'details_json',
        'discovered_at',
        'code',
        'category',
        'title',
        'description',
        'impact',
        'effort',
        'score_penalty',
        'affected_count',
        'sample_urls',
        'recommendation',
        'fix_steps',
    ];

    protected $casts = [
        'fix_steps' => 'array',
        'sample_urls' => 'array',
        'details_json' => 'array',
        'discovered_at' => 'datetime',
    ];

    const IMPACT_HIGH = 'high';
    const IMPACT_MEDIUM = 'medium';
    const IMPACT_LOW = 'low';

    const EFFORT_EASY = 'easy';
    const EFFORT_MEDIUM = 'medium';
    const EFFORT_HARD = 'hard';

    const SEVERITY_CRITICAL = 'critical';
    const SEVERITY_WARNING = 'warning';
    const SEVERITY_INFO = 'info';

    const STATUS_OPEN = 'open';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_IGNORED = 'ignored';

    /**
     * Get the audit this issue belongs to
     */
    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }
}
