<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserCampaignRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $input = $this->all();

        // Trim all string values
        foreach ($input as $key => $value) {
            if (is_string($value)) {
                $input[$key] = trim($value);
            }
        }

        // Convert empty strings to null for optional fields
        if (isset($input['domain_id']) && $input['domain_id'] === '') {
            $input['domain_id'] = null;
        }
        if (isset($input['company_state']) && $input['company_state'] === '') {
            $input['company_state'] = null;
        }
        if (isset($input['company_city']) && $input['company_city'] === '') {
            $input['company_city'] = null;
        }
        if (isset($input['gmail_account_id']) && $input['gmail_account_id'] === '') {
            $input['gmail_account_id'] = null;
        }

        // Convert string IDs to integers
        if (isset($input['company_country']) && $input['company_country'] !== null && $input['company_country'] !== '') {
            $input['company_country'] = (int) $input['company_country'];
        }
        if (isset($input['company_state']) && $input['company_state'] !== null && $input['company_state'] !== '') {
            $input['company_state'] = (int) $input['company_state'];
        }
        if (isset($input['company_city']) && $input['company_city'] !== null && $input['company_city'] !== '') {
            $input['company_city'] = (int) $input['company_city'];
        }
        if (isset($input['gmail_account_id']) && $input['gmail_account_id'] !== null && $input['gmail_account_id'] !== '') {
            $input['gmail_account_id'] = (int) $input['gmail_account_id'];
        }

        // Normalize quoted web_target values like "worldwide" to worldwide
        if (isset($input['web_target']) && is_string($input['web_target'])) {
            $input['web_target'] = trim($input['web_target'], " \t\n\r\0\x0B\"");
        }

        // Clear country_name if web_target is worldwide
        if (isset($input['web_target']) && $input['web_target'] === 'worldwide') {
            $input['country_name'] = '';
        }

        // Clear gmail/password if gmail_account_id is set
        if (isset($input['gmail_account_id']) && $input['gmail_account_id'] !== null) {
            $input['gmail'] = '';
            $input['password'] = '';
        }

        $this->merge($input);
    }

    public function rules()
    {
        // For update, company_logo is optional (can keep existing)
        $logoRule = $this->isMethod('PUT') || $this->isMethod('PATCH')
            ? 'nullable|image|mimes:jpg,jpeg,png|max:2048'
            : 'required|image|mimes:jpg,jpeg,png|max:2048';

        return [
            'name' => 'nullable|string|max:255',
            'web_name' => 'required|string|max:255',
            'web_url' => 'required|url',
            'web_keyword' => 'required|string|max:1000',
            'web_about' => 'required|string',
            'web_target' => 'required|in:worldwide,specific_country',
            'country_name' => 'required_if:web_target,specific_country|string|max:255',
            'company_name' => 'required|string|max:255',
            'company_logo' => $logoRule,
            'company_email_address' => 'required|email',
            'company_address' => 'required|string',
            'company_number' => 'required|string|max:50',
            'domain_id' => 'nullable|integer|exists:domains,id',
            'company_country' => 'required|integer|exists:countries,id',
            'company_state' => 'nullable|integer|exists:states,id',
            'company_city' => 'nullable|integer|exists:cities,id',
            'gmail_account_id' => 'nullable|integer|exists:connected_accounts,id',
            'gmail' => 'nullable|email',
            'password' => 'nullable|string',
        ];
    }

    public function messages()
    {
        return [
            'web_name.required' => 'Please provide the website name.',
            'web_url.required' => 'Please provide the website URL.',
            'web_url.url' => 'Please provide a valid URL (for example, https://example.com).',
            'web_about.required' => 'Please provide a description of the website.',
            'country_name.required_if' => 'Please select a country when targeting a specific country.',
            'web_keyword.required' => 'Please provide website keywords.',
            'web_target.required' => 'Please select ranking target: worldwide or specific country.',
            'company_logo.image' => 'Please upload a valid image for the company logo (jpg & png).',
            'company_logo.max' => 'Please ensure the company logo does not exceed 2 MB in size.',
            'company_logo.required' => 'Please provide the company logo.',
            'company_email_address.email' => 'Please provide a valid email address for the company.',
            'company_email_address.required' => 'Please provide the email address.',
            'company_name.required' => 'Please provide the company name.',
            'company_address.required' => 'Please provide the company address.',
            'company_number.required' => 'Please provide the company phone number.',
            'company_number.max' => 'Please ensure the company phone number does not exceed the allowed length.',
            'company_country.required' => 'Please select the country where the company is located.',
            'gmail.email' => 'Please provide a valid Gmail address.',
        ];
    }

    public function attributes()
    {
        return [
            'web_name' => 'Website Name',
            'web_url' => 'Website URL',
            'web_keyword' => 'Website Keywords',
            'web_about' => 'Website Description',
            'web_target' => 'Ranking Target',
            'country_name' => 'Target Country',
            'company_name' => 'Company Name',
            'company_logo' => 'Company Logo',
            'company_email_address' => 'Company Email',
            'company_address' => 'Company Address',
            'company_number' => 'Company Phone',
            'company_country' => 'Company Country',
            'company_state' => 'Company State',
            'company_city' => 'Company City',
            'gmail' => 'Gmail Address',
            'password' => 'Password',
        ];
    }
}

