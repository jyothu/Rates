<?php 

namespace App\Models;

class ServicePolicy extends \Illuminate\Database\Eloquent\Model {

	protected $fillable = array('service_id', 'season_period_id', 'charging_policy_id', 'policiable_id', 'policiable_type', 'status');
  
  	public function policiable(){

        return $this->morphTo();
    }

}

?>