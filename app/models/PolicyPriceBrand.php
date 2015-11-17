<?php 

namespace App\Models;

class PolicyPriceBand extends \Eloquent {

	protected $fillable = array('service_policy_id', 'price_band_id', 'status');
  
  	public function servicePolicy() {
        return $this->belongsTo('App\Models\ServicePolicy');
    }

}
