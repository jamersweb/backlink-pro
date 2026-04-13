<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpsertWhiteLabelReportProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'domain_id' => ['nullable', 'integer', 'exists:domains,id'],
            'client_name' => ['required', 'string', 'max:255'],
            'client_website' => ['required', 'url', 'max:500'],
            'client_company_info' => ['nullable', 'string', 'max:5000'],
            'report_title' => ['required', 'string', 'max:255'],
            'reporting_period_start' => ['required', 'date'],
            'reporting_period_end' => ['required', 'date', 'after_or_equal:reporting_period_start'],
            'target_keywords' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'recommendations' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
