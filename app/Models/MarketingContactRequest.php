<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketingContactRequest extends Model
{
    protected $fillable = [
        'inquiry_type',
        'name',
        'email',
        'company',
        'website',
        'segment',
        'budget',
        'preferred_contact',
        'message',
        'utm_json',
        'ip',
        'user_agent',
        'status',
    ];

    protected $casts = [
        'utm_json' => 'array',
    ];
}
