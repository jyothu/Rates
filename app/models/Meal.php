<?php

namespace App\Models;

class Meal extends \Eloquent
{
    
    protected $fillable = array('name', 'status', 'id');

    public function mealOptions()
    {
        return $this->hasMany('App\Models\MealOption');

    }
}
