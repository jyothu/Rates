<?php

namespace Enchanting\MigrateTs\Models;

class Currency extends \Illuminate\Database\Eloquent\Model {

	protected $fillable = array('code', 'symbol', 'name', 'status', 'id');

	public function services(){

        return $this->hasMany('Enchanting\MigrateTs\Models\Service');

    }

}

?>