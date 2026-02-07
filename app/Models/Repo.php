<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Repo extends Model
{
    protected $fillable = [
        'organization_id',
        'repo_connection_id',
        'provider',
        'repo_full_name',
        'default_branch',
        'language_hint',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    const PROVIDER_GITHUB = 'github';
    const PROVIDER_GITLAB = 'gitlab';

    const LANGUAGE_LARAVEL = 'laravel';
    const LANGUAGE_NEXTJS = 'nextjs';
    const LANGUAGE_WORDPRESS = 'wordpress';
    const LANGUAGE_SHOPIFY = 'shopify';
    const LANGUAGE_UNKNOWN = 'unknown';

    /**
     * Get the organization
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the repo connection
     */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(RepoConnection::class, 'repo_connection_id');
    }

    /**
     * Get patches for this repo
     */
    public function patches(): HasMany
    {
        return $this->hasMany(AuditPatch::class);
    }
}
