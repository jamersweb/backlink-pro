<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserGmail extends Model
{
    protected $table = 'user_gmail';

    protected $fillable = [
        'user_id',
        'company_id',
        'web_id',
        'gmail',
        'password',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(CompanyInformation::class, 'company_id');
    }

    public function webInformation()
    {
        return $this->belongsTo(WebInformation::class, 'web_id');
    }
}
