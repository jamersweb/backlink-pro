<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'project_url',
        'ga4_connected_at',
        'gsc_connected_at',
    ];

    protected $casts = [
        'ga4_connected_at' => 'datetime',
        'gsc_connected_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function keywordResearchRuns(): HasMany
    {
        return $this->hasMany(KeywordResearchRun::class);
    }
}
