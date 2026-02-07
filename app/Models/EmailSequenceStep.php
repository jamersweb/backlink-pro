<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailSequenceStep extends Model
{
    protected $fillable = [
        'sequence_id',
        'step_order',
        'delay_minutes',
        'subject',
        'template_key',
        'conditions',
    ];

    protected $casts = [
        'conditions' => 'array',
    ];

    /**
     * Get the sequence
     */
    public function sequence(): BelongsTo
    {
        return $this->belongsTo(EmailSequence::class);
    }
}
