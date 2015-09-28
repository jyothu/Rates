<?php 

namespace App\Models;

class Occupancy extends \Illuminate\Database\Eloquent\Model {
    
    protected $fillable = array('max_children', 'max_adults', 'status', 'id', 'name');

	public function serviceOptions(){

        return $this->hasMany('App\Models\ServiceOption');

    }

}

?>
