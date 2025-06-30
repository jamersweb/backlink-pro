<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebInformation extends Model
{
    protected $table = 'web_information';

    protected $fillable = [
        'user_id',
        'company_id',
        'web_name',
        'web_url',
        'web_keyword',
        'web_about',
        'web_target',
        'country_name',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // → company() relation removed for now
}
