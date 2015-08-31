<?php

namespace App\Models;

class Supplier extends \Eloquent
{
    
    protected $fillable = array('name', 'description', 'region_id', 'status', 'id');

    public function services()
    {

        return $this->hasMany('App\Models\Service');

    }

    public function region()
    {

        return $this->belongsTo('App\Models\Region');

    }
}
