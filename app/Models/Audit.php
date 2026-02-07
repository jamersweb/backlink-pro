<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Audit extends Model
{
    protected $fillable = [
        'user_id',
        'organization_id',
        'url',
        'normalized_url',
        'status',
        'mode',
        'lead_email',
        'overall_score',
        'overall_grade',
        'category_scores',
        'category_grades',
        'recommendations_count',
        'audit_kpis',
        'summary',
        'share_token',
        'is_public',
        'is_gated',
        'public_summary',
        'lead_id',
        'plan_snapshot',
        'monitor_id',
        'started_at',
        'finished_at',
        'error',
        'pages_limit',
        'crawl_depth',
        'pages_scanned',
        'pages_discovered',
        'progress_percent',
        'crawl_stats',
        'performance_summary',
    ];

    protected $casts = [
        'category_scores' => 'array',
        'category_grades' => 'array',
        'audit_kpis' => 'array',
        'summary' => 'array',
        'crawl_stats' => 'array',
        'performance_summary' => 'array',
        'public_summary' => 'array',
        'plan_snapshot' => 'array',
        'is_public' => 'boolean',
        'is_gated' => 'boolean',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    const STATUS_QUEUED = 'queued';
    const STATUS_RUNNING = 'running';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    const MODE_GUEST = 'guest';
    const MODE_AUTH = 'auth';

    /**
     * Get the pages for this audit
     */
    public function pages(): HasMany
    {
        return $this->hasMany(AuditPage::class);
    }

    /**
     * Get the issues for this audit
     */
    public function issues(): HasMany
    {
        return $this->hasMany(AuditIssue::class);
    }

    /**
     * Get the links for this audit
     */
    public function links(): HasMany
    {
        return $this->hasMany(AuditLink::class);
    }

    /**
     * Get AI generations for this audit
     */
    public function aiGenerations(): HasMany
    {
        return $this->hasMany(AiGeneration::class);
    }

    /**
     * Get knowledge chunks for this audit
     */
    public function knowledgeChunks(): HasMany
    {
        return $this->hasMany(AuditKnowledgeChunk::class);
    }

    /**
     * Get the URL queue for this audit
     */
    public function urlQueue(): HasMany
    {
        return $this->hasMany(AuditUrlQueue::class);
    }

    /**
     * Get the assets for this audit
     */
    public function assets(): HasMany
    {
        return $this->hasMany(AuditAsset::class);
    }

    /**
     * Get the organization
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the lead
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Get the user that owns this audit
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the audit is owned by a user
     */
    public function isOwnedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->user_id === $user->id;
    }

    /**
     * Check if the audit can be viewed by a user or guest
     */
    public function canBeViewedBy(?User $user, ?string $token = null): bool
    {
        // Owner can always view
        if ($user && $this->isOwnedBy($user)) {
            return true;
        }

        // Public audits can be viewed
        if ($this->is_public) {
            return true;
        }

        // Check share token
        if ($token && $this->share_token && $this->share_token === $token) {
            return true;
        }

        return false;
    }
}
