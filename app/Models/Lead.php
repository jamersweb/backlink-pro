<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lead extends Model
{
    protected $fillable = [
        'organization_id',
        'audit_id',
        'email',
        'name',
        'phone',
        'company',
        'website',
        'source',
        'status',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    const SOURCE_WIDGET = 'widget';
    const SOURCE_PUBLIC_FORM = 'public_form';
    const SOURCE_MANUAL = 'manual';
    const SOURCE_SHARE = 'share';

    const STATUS_NEW = 'new';
    const STATUS_CONTACTED = 'contacted';
    const STATUS_QUALIFIED = 'qualified';
    const STATUS_WON = 'won';
    const STATUS_LOST = 'lost';

    /**
     * Get the organization
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the audit
     */
    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }
}
