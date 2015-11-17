<?php 

namespace App\Models;

class WeekPrice extends \Eloquent {

	protected $fillable = array('price_id', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
  
  	public function price() {
        return $this->belongsTo('App\Models\Price');
    }

}
