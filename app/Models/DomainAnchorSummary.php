<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainAnchorSummary extends Model
{
    protected $fillable = [
        'run_id',
        'anchor',
        'anchor_hash',
        'count',
        'type',
    ];

    protected $casts = [
        'count' => 'integer',
    ];

    const TYPE_BRAND = 'brand';
    const TYPE_EXACT = 'exact';
    const TYPE_PARTIAL = 'partial';
    const TYPE_GENERIC = 'generic';
    const TYPE_URL = 'url';
    const TYPE_EMPTY = 'empty';

    /**
     * Get the run
     */
    public function run(): BelongsTo
    {
        return $this->belongsTo(DomainBacklinkRun::class, 'run_id');
    }

    /**
     * Generate anchor hash
     */
    public static function generateHash(string $anchor): string
    {
        return hash('sha256', mb_strtolower(trim($anchor)));
    }
}
