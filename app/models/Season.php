<?php

namespace App\Models;

class Season extends \Eloquent
{

    protected $fillable = array('name', 'service_id', 'status');

    public function service()
    {

        return $this->belongsTo('Service');

    }

    public function seasonPeriods()
    {

        return $this->hasMany('App\Models\SeasonPeriod');

    }
}
