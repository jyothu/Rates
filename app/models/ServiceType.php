<?php

namespace App\Models;

class ServiceType extends \Eloquent
{
    protected $fillable = array('name', 'ts_id');

    public function services() {
        return $this->hasMany('App\Models\Service');
    }

}
