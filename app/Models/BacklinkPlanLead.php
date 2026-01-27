<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BacklinkPlanLead extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'url',
        'industry',
        'preview_json',
        'ip',
        'user_agent',
    ];

    protected $casts = [
        'preview_json' => 'array',
    ];
}
