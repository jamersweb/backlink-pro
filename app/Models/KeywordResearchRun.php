<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KeywordResearchRun extends Model
{
    protected $fillable = [
        'user_id',
        'input_type',
        'seed_query',
        'seed_url',
        'context_text',
        'locale_country',
        'locale_language',
        'status',
        'summary_text',
        'result_count',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(KeywordResearchItem::class, 'run_id');
    }
}
