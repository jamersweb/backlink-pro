<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainAuditIssue extends Model
{
    protected $fillable = [
        'domain_audit_id',
        'domain_audit_page_id',
        'severity',
        'type',
        'message',
        'data_json',
    ];

    protected $casts = [
        'data_json' => 'array',
    ];

    const SEVERITY_CRITICAL = 'critical';
    const SEVERITY_WARNING = 'warning';
    const SEVERITY_INFO = 'info';

    /**
     * Get the audit this issue belongs to
     */
    public function audit(): BelongsTo
    {
        return $this->belongsTo(DomainAudit::class, 'domain_audit_id');
    }

    /**
     * Get the page this issue belongs to
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(DomainAuditPage::class, 'domain_audit_page_id');
    }
}
