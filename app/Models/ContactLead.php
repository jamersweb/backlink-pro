<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactLead extends Model
{
    protected $fillable = [
        'name',
        'email',
        'subject',
        'message',
        'status',
    ];

    /**
     * Lead statuses
     */
    const STATUS_NEW = 'new';
    const STATUS_CONTACTED = 'contacted';
    const STATUS_RESOLVED = 'resolved';
}
