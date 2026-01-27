<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainTask extends Model
{
    protected $fillable = [
        'domain_id',
        'user_id',
        'source',
        'title',
        'description',
        'priority',
        'impact_score',
        'effort',
        'status',
        'due_at',
        'related_url',
        'related_entity',
        'created_by',
        'planner_group',
        'checklist_json',
        'why_json',
        'estimated_minutes',
        'source_signature',
        'completed_at',
    ];

    protected $casts = [
        'related_entity' => 'array',
        'due_at' => 'datetime',
        'impact_score' => 'integer',
    ];

    const SOURCE_ANALYZER = 'analyzer';
    const SOURCE_GSC = 'gsc';
    const SOURCE_GA4 = 'ga4';
    const SOURCE_BACKLINKS = 'backlinks';
    const SOURCE_META = 'meta';
    const SOURCE_INSIGHTS = 'insights';

    const PRIORITY_P1 = 'p1';
    const PRIORITY_P2 = 'p2';
    const PRIORITY_P3 = 'p3';

    const STATUS_OPEN = 'open';
    const STATUS_DOING = 'doing';
    const STATUS_DONE = 'done';
    const STATUS_DISMISSED = 'dismissed';

    const EFFORT_LOW = 'low';
    const EFFORT_MEDIUM = 'medium';
    const EFFORT_HIGH = 'high';

    const CREATED_BY_SYSTEM = 'system';
    const CREATED_BY_USER = 'user';

    /**
     * Get the domain
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate task signature for de-duplication
     */
    public function getSignatureAttribute(): string
    {
        $type = $this->related_entity['type'] ?? $this->source;
        return hash('sha1', $type . '|' . ($this->related_url ?? '') . '|' . $this->domain_id);
    }
}
