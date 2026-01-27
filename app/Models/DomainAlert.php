<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainAlert extends Model
{
    protected $fillable = [
        'domain_id',
        'user_id',
        'type',
        'severity',
        'title',
        'message',
        'related_url',
        'related_entity',
        'is_read',
    ];

    protected $casts = [
        'related_entity' => 'array',
        'is_read' => 'boolean',
    ];

    const SEVERITY_CRITICAL = 'critical';
    const SEVERITY_WARNING = 'warning';
    const SEVERITY_INFO = 'info';

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
}
