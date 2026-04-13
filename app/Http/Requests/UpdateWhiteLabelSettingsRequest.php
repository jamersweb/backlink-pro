<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWhiteLabelSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $hexColor = ['nullable', 'regex:/^#([A-Fa-f0-9]{6})$/'];

        return [
            'enabled' => ['required', 'boolean'],
            'company_name' => ['nullable', 'string', 'max:160'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
            'website' => ['nullable', 'url', 'max:255'],
            'primary_color' => $hexColor,
            'secondary_color' => $hexColor,
            'support_email' => ['nullable', 'email:rfc', 'max:255'],
            'support_phone' => ['nullable', 'string', 'max:50'],
            'company_address' => ['nullable', 'string', 'max:1000'],
            'footer_text' => ['nullable', 'string', 'max:1000'],
            'intro_text' => ['nullable', 'string', 'max:5000'],
            'outro_text' => ['nullable', 'string', 'max:5000'],
            'report_period_days' => ['required', 'integer', 'in:7,15,30'],
            'report_sections' => ['required', 'array'],
            'report_sections.on_page' => ['required', 'array'],
            'report_sections.on_page.title_optimization' => ['required', 'boolean'],
            'report_sections.on_page.meta_descriptions' => ['required', 'boolean'],
            'report_sections.on_page.heading_structure' => ['required', 'boolean'],
            'report_sections.on_page.content_quality' => ['required', 'boolean'],
            'report_sections.on_page.internal_linking' => ['required', 'boolean'],
            'report_sections.off_page' => ['required', 'array'],
            'report_sections.off_page.backlink_quality' => ['required', 'boolean'],
            'report_sections.off_page.referring_domains' => ['required', 'boolean'],
            'report_sections.off_page.anchor_text_profile' => ['required', 'boolean'],
            'report_sections.off_page.link_velocity' => ['required', 'boolean'],
            'report_sections.technical_seo' => ['required', 'array'],
            'report_sections.technical_seo.crawlability' => ['required', 'boolean'],
            'report_sections.technical_seo.indexability' => ['required', 'boolean'],
            'report_sections.technical_seo.pagespeed' => ['required', 'boolean'],
            'report_sections.technical_seo.structured_data' => ['required', 'boolean'],
            'report_sections.technical_seo.mobile_usability' => ['required', 'boolean'],
            'use_custom_cover_title' => ['required', 'boolean'],
            'custom_cover_title' => ['nullable', 'string', 'max:160', 'required_if:use_custom_cover_title,1'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'enabled' => filter_var($this->input('enabled'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            'remove_logo' => filter_var($this->input('remove_logo'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            'use_custom_cover_title' => filter_var($this->input('use_custom_cover_title'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            'report_period_days' => (int) ($this->input('report_period_days', 30) ?: 30),
        ]);
    }
}
