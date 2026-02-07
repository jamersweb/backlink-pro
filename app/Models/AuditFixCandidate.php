<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditFixCandidate extends Model
{
    protected $fillable = [
        'audit_id',
        'issue_id',
        'code',
        'title',
        'target_platform',
        'risk',
        'confidence',
        'status',
        'generated_summary',
    ];

    protected $casts = [
        'confidence' => 'integer',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_GENERATED = 'generated';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_APPLIED = 'applied';

    const RISK_LOW = 'low';
    const RISK_MEDIUM = 'medium';
    const RISK_HIGH = 'high';

    /**
     * Get the audit
     */
    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    /**
     * Get the issue
     */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(AuditIssue::class);
    }

    /**
     * Get patches for this candidate
     */
    public function patches(): HasMany
    {
        return $this->hasMany(AuditPatch::class);
    }
}
