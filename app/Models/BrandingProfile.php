<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandingProfile extends Model
{
    protected $fillable = [
        'organization_id',
        'white_label_enabled',
        'brand_name',
        'logo_path',
        'primary_color',
        'secondary_color',
        'accent_color',
        'website',
        'support_email',
        'support_phone',
        'company_address',
        'report_footer_text',
        'report_intro_text',
        'report_outro_text',
        'report_period_days',
        'report_sections_json',
        'use_custom_cover_title',
        'custom_cover_title',
        'hide_backlinkpro_branding',
        'pdf_template',
        'email_from_name',
        'email_from_address',
    ];

    protected $casts = [
        'white_label_enabled' => 'boolean',
        'report_period_days' => 'integer',
        'report_sections_json' => 'array',
        'use_custom_cover_title' => 'boolean',
        'hide_backlinkpro_branding' => 'boolean',
    ];

    /**
     * Get the organization
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
