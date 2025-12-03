<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Domain extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'default_settings',
        'status',
    ];

    protected $casts = [
        'default_settings' => 'array',
    ];

    /**
     * Domain statuses
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    /**
     * Get the user that owns this domain
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all campaigns for this domain
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    /**
     * Get total backlinks count for this domain
     */
    public function getTotalBacklinksAttribute(): int
    {
        return $this->campaigns()
            ->withCount('backlinks')
            ->get()
            ->sum('backlinks_count');
    }

    /**
     * Scope for active domains
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}

