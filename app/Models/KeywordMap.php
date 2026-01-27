<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KeywordMap extends Model
{
    protected $table = 'keyword_map';

    protected $fillable = [
        'domain_id',
        'keyword',
        'url',
        'source',
    ];

    const SOURCE_GSC = 'gsc';
    const SOURCE_MANUAL = 'manual';
    const SOURCE_BRIEF = 'brief';

    /**
     * Get the domain
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
