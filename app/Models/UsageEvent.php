<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageEvent extends Model
{
    protected $fillable = [
        'organization_id',
        'audit_id',
        'event_type',
        'quantity',
        'metadata',
        'occurred_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    const TYPE_AUDIT_CREATED = 'audit_created';
    const TYPE_PAGE_CRAWLED = 'page_crawled';
    const TYPE_LIGHTHOUSE_RUN = 'lighthouse_run';
    const TYPE_PAGESPEED_RUN = 'pagespeed_run';
    const TYPE_PDF_EXPORT = 'pdf_export';
    const TYPE_CSV_EXPORT = 'csv_export';
    const TYPE_WIDGET_AUDIT_CREATED = 'widget_audit_created';

    /**
     * Get the organization
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the audit
     */
    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }
}
