<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FixTemplate extends Model
{
    protected $fillable = [
        'key',
        'platform',
        'description',
        'patch_strategy',
    ];

    protected $casts = [
        'patch_strategy' => 'array',
    ];

    const PLATFORM_LARAVEL = 'laravel';
    const PLATFORM_NEXTJS = 'nextjs';
    const PLATFORM_WORDPRESS = 'wordpress';
    const PLATFORM_SHOPIFY = 'shopify';
    const PLATFORM_GENERIC = 'generic';
}
