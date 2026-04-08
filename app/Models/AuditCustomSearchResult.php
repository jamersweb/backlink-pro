<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditCustomSearchResult extends Model
{
    protected $fillable = [
        'audit_id',
        'audit_page_id',
        'url',
        'rule_key',
        'rule_name',
        'target_scope',
        'match_type',
        'pattern_preview',
        'expect_match',
        'matched',
        'match_count',
        'sample_match',
        'severity',
        'error_message',
        'segment_key',
    ];

    protected $casts = [
        'expect_match' => 'boolean',
        'matched' => 'boolean',
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
