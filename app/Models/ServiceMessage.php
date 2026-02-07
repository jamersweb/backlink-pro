<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ServiceMessage extends Model
{
    protected $fillable = [
        'service_request_id',
        'sender_type',
        'sender_id',
        'message',
        'attachments',
    ];

    protected $casts = [
        'attachments' => 'array',
    ];

    const SENDER_USER = 'user';
    const SENDER_ADMIN = 'admin';
    const SENDER_PROVIDER = 'provider';

    /**
     * Get the service request
     */
    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    /**
     * Get the sender (polymorphic)
     */
    public function sender(): MorphTo
    {
        return $this->morphTo('sender');
    }
}
