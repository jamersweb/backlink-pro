<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisavowEntry extends Model
{
    protected $fillable = [
        'disavow_file_id',
        'entry_type',
        'value',
        'value_hash',
        'reason',
    ];

    const ENTRY_TYPE_DOMAIN = 'domain';
    const ENTRY_TYPE_URL = 'url';

    /**
     * Get the disavow file
     */
    public function disavowFile(): BelongsTo
    {
        return $this->belongsTo(DisavowFile::class);
    }

    /**
     * Generate value hash
     */
    public static function generateHash(string $value): string
    {
        return hash('sha256', strtolower(trim($value)));
    }
}
