<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserCampaignRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'web_name'      => 'required|string|max:255',
            'web_url'       => 'required|url',
            'web_keyword'   => 'required|string|max:255',
            'web_about'     => 'required|string',
            'web_target'    => 'required|in:worldwide,specific_country',
            'country_name'  => 'required_if:web_target,specific_country|string|max:255',
            'company_name'          => 'required|string|max:255',
            'company_logo'          => 'required|image|mimes:jpg,jpeg,png|max:2048',
            'company_email_address' => 'required|email',
            'company_address'       => 'required|string',
            'company_number'        => 'required|string|max:50',
            'company_country'       => 'required|string|max:255',
            'company_state'         => 'required|string|max:255',
            'company_city'          => 'nullable|string|max:255',
            'gmail'    => 'required|email',
            'password' => 'required|string',
        ];
    }

    public function messages()
    {
        return [
    'web_name.required'         => 'Please provide the website name.',
    'web_url.required'          => 'Please provide the website URL.',
    'web_url.url'               => 'Please provide a valid URL (for example, https://example.com).',
    'web_about.required'        => 'Please provide a description of the website.',
    'country_name.required_if'  => 'Please select a country when the ranking target is set to a specific country.',
    'web_keyword.required'     => 'Please provide website keywords.',
    'web_target.required'      => 'Please select ranking target: worldwide or specific country.',
    'country_name.required_if' => 'Please select a country when targeting a specific country.',
    'company_logo.image'        => 'Please upload a valid image for the company logo (jpg & png).',
    'company_logo.max'          => 'Please ensure the company logo does not exceed 2 MB in size.',
    'company_logo.required'          => 'Please provide the company logo .',
    'company_email_address.email'=> 'Please provide a valid email address for the company.',
    'company_email_address.required'=> 'Please provide the email address .',
    'company_name.required'        => 'Please provide the company name.',
    'company_address.required'     => 'Please provide the company address.',
    'company_number.required'      => 'Please provide the company phone number.',
    'company_number.max'           => 'Please ensure the company phone number does not exceed.',
    'company_country.required'  => 'Please select the country where the company is located.',
    'company_state.required'    => 'Please select the state where the company is located.',
    'company_city.required'     => 'Please select the city where the company is located.',
    'gmail.required'            => 'Please provide your Gmail address.',
    'gmail.email'               => 'Please provide a valid Gmail address.',
    'password.required'         => 'Please provide a password.',
];

    }

    public function attributes()
    {
        return [
            'web_name'                => 'Website Name',
            'web_url'                 => 'Website URL',
            'web_keyword'             => 'Website Keywords',
            'web_about'               => 'Website Description',
            'web_target'              => 'Ranking Target',
            'country_name'            => 'Target Country',
            'company_name'            => 'Company Name',
            'company_logo'            => 'Company Logo',
            'company_email_address'   => 'Company Email',
            'company_address'         => 'Company Address',
            'company_number'          => 'Company Phone',
            'company_country'         => 'Company Country',
            'company_state'           => 'Company State',
            'company_city'            => 'Company City',
            'gmail'                   => 'Gmail Address',
            'password'                => 'Password',
        ];
    }
}
