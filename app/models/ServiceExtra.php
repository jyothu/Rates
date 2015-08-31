<?php

namespace App\Models;

class ServiceExtra extends \Eloquent
{
    
    protected $fillable = array('name', 'service_option_id', 'mandatory', 'status', 'id');

    public function prices()
    {

        return $this->morphMany('App\Models\Price', 'priceable');
    }

    public function serviceOption()
    {

        return $this->belongsTo('App\Models\ServiceOption');

    }
}
