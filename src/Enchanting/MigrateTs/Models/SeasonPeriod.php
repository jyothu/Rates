<?php 

namespace Enchanting\MigrateTs\Models;

class SeasonPeriod extends \Illuminate\Database\Eloquent\Model {

	protected $fillable = array('season_id', 'start', 'end', 'status');

    public function season(){

        return $this->belongsTo('Enchanting\MigrateTs\Models\Season');

    }

	public function price(){

        return $this->hasOne('Enchanting\MigrateTs\Models\Price');

    }

}

?>