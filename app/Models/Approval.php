<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Approval extends Model
{
    protected $fillable = [
        'organization_id',
        'client_id',
        'client_project_id',
        'subject_type',
        'subject_id',
        'title',
        'summary',
        'requested_by_user_id',
        'status',
        'decision_by_user_id',
        'decision_note',
        'requested_at',
        'decided_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'decided_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(ClientProject::class, 'client_project_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decision_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ApprovalItem::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(SeoAlertRule::class, 'rule_id');
    }
}
