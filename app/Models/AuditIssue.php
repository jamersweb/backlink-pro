<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditIssue extends Model
{
    protected $fillable = [
        'audit_id',
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
    ];

    const IMPACT_HIGH = 'high';
    const IMPACT_MEDIUM = 'medium';
    const IMPACT_LOW = 'low';

    const EFFORT_EASY = 'easy';
    const EFFORT_MEDIUM = 'medium';
    const EFFORT_HARD = 'hard';

    /**
     * Get the audit this issue belongs to
     */
    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }
}
