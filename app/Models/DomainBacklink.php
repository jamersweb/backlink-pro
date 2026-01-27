<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainBacklink extends Model
{
    protected $fillable = [
        'run_id',
        'fingerprint',
        'source_url',
        'source_domain',
        'target_url',
        'anchor',
        'rel',
        'first_seen',
        'last_seen',
        'tld',
        'country',
        'risk_flags_json',
        'ref_domain_id',
        'quality_score',
        'risk_score',
        'flags_json',
        'action_status',
    ];

    protected $casts = [
        'first_seen' => 'date',
        'last_seen' => 'date',
        'risk_flags_json' => 'array',
        'flags_json' => 'array',
    ];

    const REL_FOLLOW = 'follow';
    const REL_NOFOLLOW = 'nofollow';
    const REL_UGC = 'ugc';
    const REL_SPONSORED = 'sponsored';
    const REL_UNKNOWN = 'unknown';

    const ACTION_KEEP = 'keep';
    const ACTION_REVIEW = 'review';
    const ACTION_REMOVE = 'remove';
    const ACTION_DISAVOW = 'disavow';

    /**
     * Get the run
     */
    public function run(): BelongsTo
    {
        return $this->belongsTo(DomainBacklinkRun::class, 'run_id');
    }

    /**
     * Get the referring domain
     */
    public function refDomain(): BelongsTo
    {
        return $this->belongsTo(BacklinkRefDomain::class, 'ref_domain_id');
    }

    /**
     * Get tags
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(BacklinkTag::class, 'backlink_item_tag', 'backlink_item_id', 'backlink_tag_id')
            ->withTimestamps();
    }

    /**
     * Generate fingerprint
     */
    public static function generateFingerprint(string $sourceUrl, string $targetUrl, string $rel, ?string $anchor): string
    {
        return hash('sha256', $sourceUrl . '|' . $targetUrl . '|' . $rel . '|' . ($anchor ?? ''));
    }
}
