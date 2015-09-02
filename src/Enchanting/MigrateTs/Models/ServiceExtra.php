<?php 

namespace App\Models;

class ServiceExtra extends \Illuminate\Database\Eloquent\Model {
    
    protected $fillable = array('name', 'service_option_id', 'mandatory', 'status', 'id');

    public function prices(){

        return $this->morphMany('App\Models\Price', 'priceable');
    }

    public function service_option(){

        return $this->belongsTo('App\Models\ServiceOption');

    }

}

?>