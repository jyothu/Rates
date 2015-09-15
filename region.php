<?php
include "vendor/autoload.php";
include "config/database.php";

use App\Models\Region as Region;

foreach (Region::all() as $region) {

	$parent = Region::where('ts_id', '=', $region->parent_id)->first();
	$region->update(array('parent_id' => $parent->id));
 	
 } 


 ?>