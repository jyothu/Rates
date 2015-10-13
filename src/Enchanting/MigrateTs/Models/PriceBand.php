<?php 

namespace App\Models;

class PriceBand extends \Illuminate\Database\Eloquent\Model {

	protected $fillable = array('ts_id', 'name', 'min', 'max');
    
}

?>