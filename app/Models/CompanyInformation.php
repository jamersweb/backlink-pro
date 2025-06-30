<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyInformation extends Model
{
    protected $table = 'company_information';

    protected $fillable = [
        'user_id',
        'web_id', 
        'company_name',
        'company_logo',
        'company_email_address',
        'company_address',
        'company_number',
        'company_country',
        'company_city',
        'company_state',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
