<?php 

namespace App\Models;

class ServicePolicy extends \Eloquent
{
	protected $fillable = array('price_id', 'charging_policy_id', 'status');
  
  	public function price() {
        return $this->belongsTo('App\Models\Price');
    }
    
    public function policyPriceBands() {
        return $this->hasMany('App\Models\PolicyPriceBand');
    }
}