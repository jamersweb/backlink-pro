<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RankKeyword extends Model
{
    protected $fillable = [
        'rank_project_id',
        'domain_id', // Legacy support
        'keyword',
        'target_url',
        'location',
        'location_code', // Legacy
        'language_code',
        'device',
        'schedule',
        'is_active',
        'source',
        'tags',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_active' => 'boolean',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    const DEVICE_DESKTOP = 'desktop';
    const DEVICE_MOBILE = 'mobile';

    const SCHEDULE_DAILY = 'daily';
    const SCHEDULE_WEEKLY = 'weekly';
    const SCHEDULE_MANUAL = 'manual';

    const SOURCE_MANUAL = 'manual';
    const SOURCE_KEYWORD_MAP = 'keyword_map';
    const SOURCE_GSC = 'gsc';
    const SOURCE_BRIEF = 'brief';

    /**
     * Get the rank project
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(RankProject::class, 'rank_project_id');
    }

    /**
     * Get the domain (legacy)
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * Get rank results
     */
    public function results(): HasMany
    {
        return $this->hasMany(RankResult::class);
    }

    /**
     * Get latest result
     */
    public function latestResult()
    {
        return $this->results()->latest('fetched_at')->first();
    }
}
