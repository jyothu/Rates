<?php 

namespace App\Models;

class PriceBrand extends \Illuminate\Database\Eloquent\Model {

	protected $fillable = array('ts_id', 'name', 'min', 'max');
    
}

?>