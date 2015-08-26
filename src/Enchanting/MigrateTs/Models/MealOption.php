<?php 

namespace Enchanting\MigrateTs\Models;

class MealOption extends \Illuminate\Database\Eloquent\Model {
    
    protected $fillable = array('service_option_id', 'meal_id', 'status', 'id');

    public function meal(){

        return $this->belongsTo('Enchanting\MigrateTs\Models\Meal');

    }

}

?>
