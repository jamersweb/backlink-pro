<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    // temporarily allow all fields
   protected $fillable = [
      'user_id',
      'web_name','web_url','web_keyword','web_about','web_target','country_name',
      'company_name','company_logo','company_email_address','company_address','company_number',
      'company_country','company_state','company_city',
      'gmail','password','status',
    ];


    // relations
    public function user() { return $this->belongsTo(User::class); }
    public function country() { return $this->belongsTo(Country::class, 'company_country'); }
    public function state()   { return $this->belongsTo(State::class, 'company_state'); }
    public function city()    { return $this->belongsTo(City::class, 'company_city'); }
}
