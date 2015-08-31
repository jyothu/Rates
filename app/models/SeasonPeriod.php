<?php

namespace App\Models;

class SeasonPeriod extends \Eloquent
{

    protected $fillable = array('season_id', 'start', 'end', 'status');

    public function season()
    {

        return $this->belongsTo('App\Models\Season');

    }

    public function price()
    {

        return $this->hasOne('App\Models\Price');

    }
}
