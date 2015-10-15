<?php 

namespace App\Models;

class PriceBand extends \Eloquent
{
	protected $fillable = array('ts_id', 'name', 'min', 'max');   
}