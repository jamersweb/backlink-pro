<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiPromptTemplate extends Model
{
    protected $fillable = [
        'key',
        'system_prompt',
        'user_prompt',
        'output_schema',
        'is_active',
        'version',
    ];

    protected $casts = [
        'output_schema' => 'array',
        'is_active' => 'boolean',
    ];
}
