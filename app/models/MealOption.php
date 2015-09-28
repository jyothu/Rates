<?php 

namespace App\Models;

class MealOption extends \Eloquent
{
    
    protected $fillable = array('service_option_id', 'meal_id', 'status', 'id');

    public function meal()
    {

        return $this->belongsTo('App\Models\Meal');

    }
}
