<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditPatch extends Model
{
    protected $fillable = [
        'audit_fix_candidate_id',
        'repo_id',
        'branch_name',
        'commit_sha',
        'pr_url',
        'patch_unified_diff',
        'files_touched',
        'apply_instructions',
        'test_instructions',
        'status',
        'error',
    ];

    protected $casts = [
        'files_touched' => 'array',
    ];

    const STATUS_READY = 'ready';
    const STATUS_PR_OPENED = 'pr_opened';
    const STATUS_FAILED = 'failed';

    /**
     * Get the fix candidate
     */
    public function fixCandidate(): BelongsTo
    {
        return $this->belongsTo(AuditFixCandidate::class);
    }

    /**
     * Get the repo
     */
    public function repo(): BelongsTo
    {
        return $this->belongsTo(Repo::class);
    }
}
