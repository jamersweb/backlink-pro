<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BacklinkTag extends Model
{
    protected $fillable = [
        'domain_id',
        'name',
        'color',
    ];

    /**
     * Get the domain
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * Get backlinks with this tag
     */
    public function backlinks(): BelongsToMany
    {
        return $this->belongsToMany(DomainBacklink::class, 'backlink_item_tag', 'backlink_tag_id', 'backlink_item_id')
            ->withTimestamps();
    }
}
