<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartnerApplication extends Model
{
    protected $fillable = [
        'partner_type',
        'name',
        'email',
        'company',
        'website',
        'company_size',
        'client_count',
        'regions',
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
