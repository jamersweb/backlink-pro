<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainAuditMetric extends Model
{
    protected $fillable = [
        'domain_audit_id',
        'url',
        'strategy',
        'performance_score',
        'lcp_ms',
        'cls_x1000',
        'inp_ms',
        'fcp_ms',
        'ttfb_ms',
        'raw_json',
    ];

    protected $casts = [
        'performance_score' => 'integer',
        'lcp_ms' => 'integer',
        'cls_x1000' => 'integer',
        'inp_ms' => 'integer',
        'fcp_ms' => 'integer',
        'ttfb_ms' => 'integer',
        'raw_json' => 'array',
    ];

    const STRATEGY_MOBILE = 'mobile';
    const STRATEGY_DESKTOP = 'desktop';

    /**
     * Get the audit this metric belongs to
     */
    public function audit(): BelongsTo
    {
        return $this->belongsTo(DomainAudit::class, 'domain_audit_id');
    }

    /**
     * Get CLS as decimal (divide by 1000)
     */
    public function getClsAttribute(): ?float
    {
        return $this->cls_x1000 ? $this->cls_x1000 / 1000 : null;
    }
}
