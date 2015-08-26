<?php 

namespace Enchanting\MigrateTs\Models;

class Occupancy extends \Illuminate\Database\Eloquent\Model {
    
    protected $fillable = array('min_adults', 'max_adults', 'status', 'id');

	public function service_options(){

        return $this->hasMany('Enchanting\MigrateTs\Models\ServiceOption');

    }

}

?>
