<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BacklinkOpportunity extends Model
{
    use HasFactory;

    protected $fillable = [
        'url',
        'pa',
        'da',
        'site_type',
        'status',
        'daily_site_limit',
        'metadata',
    ];

    protected $casts = [
        'pa' => 'integer',
        'da' => 'integer',
        'daily_site_limit' => 'integer',
        'metadata' => 'array',
    ];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'backlink_opportunity_category')
            ->withTimestamps();
    }

    public function backlinks(): HasMany
    {
        return $this->hasMany(Backlink::class, 'backlink_opportunity_id');
    }
}


