<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FreePlanRequest extends Model
{
    protected $fillable = [
        'website',
        'segment',
        'risk_mode',
        'goals',
        'target_pages',
        'competitors',
        'monthly_budget',
        'email',
        'utm_json',
        'ip',
        'user_agent',
        'status',
    ];

    protected $casts = [
        'goals' => 'array',
        'target_pages' => 'array',
        'competitors' => 'array',
        'utm_json' => 'array',
    ];
}
