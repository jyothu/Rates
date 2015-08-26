<?php

namespace Enchanting\MigrateTs;

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