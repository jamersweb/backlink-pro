<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrgMetricsDaily extends Model
{
    protected $fillable = [
        'organization_id',
        'date',
        'audits_created',
        'pages_crawled',
        'lighthouse_runs',
        'pdf_exports',
        'leads_generated',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Get the organization
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
