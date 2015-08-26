<?php 

namespace Enchanting\MigrateTs\Models;

class Meal extends \Illuminate\Database\Eloquent\Model {
    
    protected $fillable = array('name', 'status', 'id');

	public function meal_options(){

        return $this->hasMany('Enchanting\MigrateTs\Models\MealOption');

    }

}

?>
