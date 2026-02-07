<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandingProfile extends Model
{
    protected $fillable = [
        'organization_id',
        'brand_name',
        'logo_path',
        'primary_color',
        'secondary_color',
        'accent_color',
        'report_footer_text',
        'hide_backlinkpro_branding',
        'pdf_template',
        'email_from_name',
        'email_from_address',
    ];

    protected $casts = [
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
