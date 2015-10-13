<?php 

namespace App\Models;

class ServicePriceBrand extends \Illuminate\Database\Eloquent\Model {

	protected $fillable = array('service_id', 'season_period_id', 'price_brand_id', 'brandable_id', 'brandable_type', 'status');
  
  	public function brandable(){

        return $this->morphTo();
    }

}

?>