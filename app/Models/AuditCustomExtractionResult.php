<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditCustomExtractionResult extends Model
{
    protected $fillable = [
        'audit_id',
        'audit_page_id',
        'url',
        'rule_key',
        'rule_name',
        'target_scope',
        'extraction_type',
        'extractor',
        'attribute',
        'multiple',
        'values',
        'missing',
        'error_message',
        'segment_key',
        'fingerprint',
    ];

    protected $casts = [
        'multiple' => 'boolean',
        'values' => 'array',
        'missing' => 'boolean',
    ];

    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(AuditPage::class, 'audit_page_id');
    }
}
