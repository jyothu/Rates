<?php
namespace App\Models;

class Occupancy extends \Eloquent
{
    protected $fillable = array('min_adults', 'max_adults', 'status', 'name');

    public function serviceOptions(){
        return $this->hasMany('App\Models\ServiceOption');
    }
}
