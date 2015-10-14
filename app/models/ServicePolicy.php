<?php 

namespace App\Models;

class ServicePolicy extends \Eloquent
{
	protected $fillable = array('service_id', 'season_period_id', 'charging_policy_id', 'policiable_id', 'policiable_type', 'status');
  
  	public function price() {
        return $this->belongsTo('App\Models\Price');
    }

}