<?php 

namespace App\Models;

class ServicePriceBand extends \Illuminate\Database\Eloquent\Model {

	protected $fillable = array('service_id', 'season_period_id', 'price_band_id', 'bandable_id', 'bandable_type', 'status');
  
  	public function bandable(){

        return $this->morphTo();
    }

}

?>