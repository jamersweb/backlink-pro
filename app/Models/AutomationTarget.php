<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;

class AutomationTarget extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'campaign_id',
        'url',
        'url_hash',
        'source',
        'anchor_text',
        'target_link',
        'keyword',
        'metadata_json',
        'created_at',
    ];

    protected $casts = [
        'metadata_json' => 'array',
        'created_at' => 'datetime',
    ];

    const SOURCE_MANUAL = 'manual';
    const SOURCE_CSV = 'csv';
    const SOURCE_BACKLINKS_RUN = 'backlinks_run';
    const SOURCE_INSIGHTS = 'insights';

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($target) {
            if (empty($target->url_hash)) {
                $target->url_hash = hash('sha256', $target->url);
            }
        });
    }

    /**
     * Get the campaign
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AutomationCampaign::class);
    }

    /**
     * Get jobs for this target
     */
    public function jobs(): HasMany
    {
        return $this->hasMany(AutomationJob::class);
    }
}
