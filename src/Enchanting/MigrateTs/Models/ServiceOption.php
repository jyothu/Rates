<?php 

namespace Enchanting\MigrateTs\Models;

class ServiceOption extends \Illuminate\Database\Eloquent\Model {
    
    protected $fillable = array('service_id', 'occupancy_id', 'name', 'has_extra', 'status');

    public function prices(){

        return $this->morphMany('Enchanting\MigrateTs\Models\Price', 'priceable');
        
    }

    public function service_extras(){

        return $this->hasMany('Enchanting\MigrateTs\Models\ServiceExtra');
    
    }

    public function meal_options(){

        return $this->hasMany('Enchanting\MigrateTs\Models\MealOption');
    
    }

    public function occupancy(){

        return $this->belongsTo('Enchanting\MigrateTs\Models\Occupancy');

    }

    public function service(){

        return $this->belongsTo('Enchanting\MigrateTs\Models\Service');

    }

}

?>