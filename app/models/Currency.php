<?php

namespace App\Models;

class Currency extends \Eloquent
{

    protected $fillable = array('code', 'symbol', 'name', 'status', 'id');

    public function services()
    {
        return $this->hasMany('App\Models\Service');
    }
}
