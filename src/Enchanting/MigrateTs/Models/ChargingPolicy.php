<?php 

namespace App\Models;

class ChargingPolicy extends \Illuminate\Database\Eloquent\Model {

	protected $fillable = array('ts_id', 'name', 'charging_duration', 'day_duration', 'room_based', 'day_overlap', 'capacity', 'status');
    
}

?>