<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceRequest extends Model
{
    protected $fillable = [
        'organization_id',
        'lead_id',
        'audit_id',
        'requested_by_user_id',
        'status',
        'priority',
        'total_price_cents',
        'currency',
        'notes',
        'scope',
    ];

    protected $casts = [
        'scope' => 'array',
    ];

    const STATUS_NEW = 'new';
    const STATUS_QUOTED = 'quoted';
    const STATUS_APPROVED = 'approved';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CLOSED = 'closed';
    const STATUS_CANCELED = 'canceled';

    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';

    /**
     * Get the organization
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the lead
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Get the audit
     */
    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    /**
     * Get the requester
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    /**
     * Get service request items
     */
    public function items(): HasMany
    {
        return $this->hasMany(ServiceRequestItem::class);
    }

    /**
     * Get messages
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ServiceMessage::class);
    }

    /**
     * Get assignments
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(ServiceAssignment::class);
    }
}
