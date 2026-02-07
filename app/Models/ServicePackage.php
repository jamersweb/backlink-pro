<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServicePackage extends Model
{
    protected $fillable = [
        'organization_id',
        'name',
        'description',
        'monthly_price_cents',
        'currency',
        'included_deliverables',
        'sla_days',
        'is_active',
    ];

    protected $casts = [
        'included_deliverables' => 'array',
        'sla_days' => 'array',
        'is_active' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(ClientSubscription::class);
    }
}
