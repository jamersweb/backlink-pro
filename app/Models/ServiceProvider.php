<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceProvider extends Model
{
    protected $fillable = [
        'organization_id',
        'name',
        'email',
        'status',
        'specialties',
        'rating',
    ];

    protected $casts = [
        'specialties' => 'array',
        'rating' => 'decimal:1',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    /**
     * Get the organization (if partner)
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get assignments
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(ServiceAssignment::class);
    }
}
