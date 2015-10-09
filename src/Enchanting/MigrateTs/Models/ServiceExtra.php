<?php 

namespace App\Models;

class ServiceExtra extends \Illuminate\Database\Eloquent\Model {
    
    protected $fillable = array('name', 'service_id', 'policy_id', 'mandatory', 'status', 'ts_id');

    public function prices(){

        return $this->morphMany('App\Models\Price', 'priceable');
    }

    public function service(){

        return $this->belongsTo('App\Models\Service');

    }

    public function serviceOptions(){

        return $this->hasMany('App\Models\ServiceOption');

    }

    public function policy(){

        return $this->belongsTo('App\Models\Policy');

    }

}

?>