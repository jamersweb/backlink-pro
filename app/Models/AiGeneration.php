<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiGeneration extends Model
{
    protected $fillable = [
        'organization_id',
        'audit_id',
        'lead_id',
        'type',
        'input_fingerprint',
        'model_key',
        'prompt_version',
        'status',
        'input',
        'output',
        'tokens_in',
        'tokens_out',
        'cost_cents',
        'error',
    ];

    protected $casts = [
        'input' => 'array',
        'output' => 'array',
    ];

    const TYPE_REPORT_SUMMARY = 'report_summary';
    const TYPE_FIX_PLAN = 'fix_plan';
    const TYPE_SNIPPET_PACK = 'snippet_pack';
    const TYPE_CHAT_ANSWER = 'chat_answer';
    const TYPE_COMPETITOR_SUMMARY = 'competitor_summary';

    const STATUS_QUEUED = 'queued';
    const STATUS_RUNNING = 'running';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

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

    /**
     * Get the lead
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
