<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SnippetInstallation extends Model
{
    protected $fillable = [
        'domain_id',
        'key',
        'status',
        'first_seen_at',
        'last_seen_at',
        'last_origin_host',
        'agent_version',
        'settings_json',
    ];

    protected $casts = [
        'settings_json' => 'array',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    const STATUS_UNKNOWN = 'unknown';
    const STATUS_VERIFIED = 'verified';
    const STATUS_ERROR = 'error';

    /**
     * Get the domain
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * Get events
     */
    public function events(): HasMany
    {
        return $this->hasMany(SnippetEvent::class, 'domain_id', 'domain_id');
    }

    /**
     * Get commands
     */
    public function commands(): HasMany
    {
        return $this->hasMany(SnippetCommand::class, 'domain_id', 'domain_id');
    }
}
