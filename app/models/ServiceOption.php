<?php

namespace App\Models;

class ServiceOption extends \Eloquent
{
    
    protected $fillable = array('service_id', 'occupancy_id', 'name', 'has_extra', 'status');

    public function prices()
    {

        return $this->morphMany('App\Models\Price', 'priceable');
        
    }

    public function serviceExtras()
    {

        return $this->hasMany('App\Models\ServiceExtra');
    
    }

    public function mealOptions()
    {

        return $this->hasMany('App\Models\MealOption');
    
    }

    public function occupancy()
    {

        return $this->belongsTo('App\Models\Occupancy');

    }

    public function service()
    {

        return $this->belongsTo('App\Models\Service');

    }
}
