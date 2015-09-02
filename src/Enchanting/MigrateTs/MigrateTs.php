<?php

namespace App;

/**
* 
*/
class Query
{

	function findOrCreateByName( $model, $name ){

		return $model::firstOrCreate(array('name' => $name));
	}
}

?>