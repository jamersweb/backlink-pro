<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainPlan extends Model
{
    protected $fillable = [
        'domain_id',
        'user_id',
        'status',
        'period_days',
        'plan_json',
        'generated_by',
        'generated_at',
        'applied_at',
    ];

    protected $casts = [
        'plan_json' => 'array',
        'generated_at' => 'datetime',
        'applied_at' => 'datetime',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_APPLIED = 'applied';
    const STATUS_ARCHIVED = 'archived';

    const GENERATED_BY_HEURISTIC = 'heuristic';
    const GENERATED_BY_LLM = 'llm';

    /**
     * Get the domain
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
