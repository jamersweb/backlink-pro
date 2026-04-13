<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateWhiteLabelReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'profile_id' => ['required', 'integer', 'exists:white_label_report_profiles,id'],
            'reporting_period_start' => ['required', 'date'],
            'reporting_period_end' => ['required', 'date', 'after_or_equal:reporting_period_start'],
            'report_title' => ['nullable', 'string', 'max:255'],
        ];
    }
}
