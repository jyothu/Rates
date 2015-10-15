<?php 

namespace App\Models;

class ServicePriceBand extends \Eloquent
{
	protected $fillable = array('price_id', 'price_band_id', 'status');
  
  	public function price() {
        return $this->belongsTo('App\Models\Price');
    }

}