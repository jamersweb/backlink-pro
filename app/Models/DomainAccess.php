<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainAccess extends Model
{
    protected $fillable = [
        'domain_id',
        'team_id',
        'user_id',
        'role',
        'permissions_json',
    ];

    protected $casts = [
        'permissions_json' => 'array',
    ];

    const ROLE_ADMIN = 'admin';
    const ROLE_EDITOR = 'editor';
    const ROLE_VIEWER = 'viewer';

    /**
     * Get the domain
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * Get the team
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the user (nullable)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
