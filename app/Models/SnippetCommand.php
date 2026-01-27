<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SnippetCommand extends Model
{
    protected $fillable = [
        'domain_id',
        'command',
        'status',
        'payload_json',
    ];

    protected $casts = [
        'payload_json' => 'array',
    ];

    const COMMAND_PING = 'ping';
    const COMMAND_REFRESH_META = 'refresh_meta';
    const COMMAND_VERIFY = 'verify';

    const STATUS_QUEUED = 'queued';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_COMPLETED = 'completed';
    const STATUS_EXPIRED = 'expired';

    /**
     * Get the domain
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
