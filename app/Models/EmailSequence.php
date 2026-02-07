<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailSequence extends Model
{
    protected $fillable = [
        'key',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get sequence steps
     */
    public function steps(): HasMany
    {
        return $this->hasMany(EmailSequenceStep::class)->orderBy('step_order');
    }
}
