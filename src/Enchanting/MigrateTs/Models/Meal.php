<?php 

namespace App\Models;

class Meal extends \Illuminate\Database\Eloquent\Model {
    
    protected $fillable = array('name', 'status');

	public function meal_options(){

        return $this->hasMany('App\Models\MealOption');

    }

}

?>
