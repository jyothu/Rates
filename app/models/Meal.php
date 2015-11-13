<?php

namespace App\Models;

class Meal extends \Eloquent
{
    
    protected $fillable = array('name', 'status');

    public function mealOptions(){
        return $this->hasMany('App\Models\MealOption');
    }

    public function serviceOptions(){
    	return $this->belongsToMany('App\Models\ServiceOption', 'meal_options');
    }
    
}
