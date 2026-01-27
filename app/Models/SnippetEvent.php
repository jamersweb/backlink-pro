<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SnippetEvent extends Model
{
    protected $fillable = [
        'domain_id',
        'date',
        'path',
        'views',
        'uniques',
    ];

    protected $casts = [
        'date' => 'date',
        'views' => 'integer',
        'uniques' => 'integer',
    ];

    /**
     * Get the domain
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
