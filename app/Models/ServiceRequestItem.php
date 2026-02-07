<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceRequestItem extends Model
{
    protected $fillable = [
        'service_request_id',
        'service_catalog_id',
        'quantity',
        'unit_price_cents',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    /**
     * Get the service request
     */
    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    /**
     * Get the service catalog item
     */
    public function serviceCatalog(): BelongsTo
    {
        return $this->belongsTo(ServiceCatalog::class);
    }

    /**
     * Get total price for this item
     */
    public function getTotalPriceCentsAttribute(): int
    {
        return $this->unit_price_cents * $this->quantity;
    }
}
