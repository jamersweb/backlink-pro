<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DisavowFile extends Model
{
    protected $fillable = [
        'domain_id',
        'user_id',
        'version',
        'status',
        'notes',
        'file_text',
        'generated_at',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_EXPORTED = 'exported';
    const STATUS_ARCHIVED = 'archived';

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

    /**
     * Get entries
     */
    public function entries(): HasMany
    {
        return $this->hasMany(DisavowEntry::class);
    }
}
