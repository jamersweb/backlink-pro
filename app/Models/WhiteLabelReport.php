<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhiteLabelReport extends Model
{
    protected $fillable = [
        'organization_id',
        'user_id',
        'white_label_report_profile_id',
        'domain_id',
        'client_name',
        'client_website',
        'report_title',
        'reporting_period_start',
        'reporting_period_end',
        'status',
        'generated_at',
        'snapshot_json',
    ];

    protected $casts = [
        'reporting_period_start' => 'date',
        'reporting_period_end' => 'date',
        'generated_at' => 'datetime',
        'snapshot_json' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(WhiteLabelReportProfile::class, 'white_label_report_profile_id');
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
