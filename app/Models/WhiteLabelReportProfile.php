<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhiteLabelReportProfile extends Model
{
    protected $fillable = [
        'organization_id',
        'user_id',
        'domain_id',
        'client_name',
        'client_website',
        'report_title',
        'reporting_period_start',
        'reporting_period_end',
        'target_keywords',
        'notes',
        'recommendations',
    ];

    protected $casts = [
        'reporting_period_start' => 'date',
        'reporting_period_end' => 'date',
    ];

    /**
     * Get the owning organization.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the owning user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the linked domain when available.
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
