<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $fillable = [
        'organization_id',
        'name',
        'primary_domain',
        'industry',
        'status',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(ClientUser::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(ClientProject::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(ClientSubscription::class);
    }

    public function deliverables(): HasMany
    {
        return $this->hasMany(Deliverable::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(ClientConversation::class);
    }

    public function slaEvents(): HasMany
    {
        return $this->hasMany(SlaEvent::class);
    }
}
