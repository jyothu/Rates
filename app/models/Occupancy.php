<?php

namespace App\Models;

class Occupancy extends \Eloquent
{
    
    protected $fillable = array('min_adults', 'max_adults', 'status', 'id');

    public function serviceOptions()
    {
        return $this->hasMany('Enchanting\MigrateTs\Models\ServiceOption');

    }
}
