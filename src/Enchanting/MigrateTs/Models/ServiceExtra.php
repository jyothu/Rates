<?php 

namespace App\Models;

class ServiceExtra extends \Illuminate\Database\Eloquent\Model {
    
    protected $fillable = array('name', 'service_id', 'mandatory', 'status', 'ts_id');

    public function prices(){

        return $this->morphMany('App\Models\Price', 'priceable');
    }

    public function servicePolicies(){

        return $this->morphMany('App\Models\ServicePolicy', 'policiable');
        
    }

    public function service(){

        return $this->belongsTo('App\Models\Service');

    }

    public function serviceOptions(){

        return $this->hasMany('App\Models\ServiceOption');

    }

}

?>