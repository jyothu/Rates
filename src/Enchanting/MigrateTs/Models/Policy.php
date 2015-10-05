<?php 

namespace App\Models;

class Policy extends \Illuminate\Database\Eloquent\Model {

	protected $fillable = array('ts_id', 'name', 'charging_duration', 'day_duration', 'room_based', 'day_overlap', 'capacity', 'status');


	public function serviceOptions(){

        return $this->hasMany('App\Models\ServiceOption');

    }
    
}

?>