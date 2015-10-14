<?php

namespace App\Models;

class Region extends \Eloquent
{    
    protected $fillable = array('name', 'ts_id', 'parent_id');

    public function services() {
        return $this->hasMany('App\Models\Service');
    }

    public function suppliers() {
        return $this->hasMany('App\Models\Supplier');
    }

    public function country() {
        return $this->belongsTo('App\Models\Region', 'parent_id');
    }

    public function regions() {
        return $this->hasMany('App\Models\Region', 'parent_id');
    }
}
