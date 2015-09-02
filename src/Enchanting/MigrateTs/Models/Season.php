<?php 

namespace App\Models;

class Season extends \Illuminate\Database\Eloquent\Model {

	protected $fillable = array('name', 'service_id', 'status');

    public function service(){

        return $this->belongsTo('Service');

    }

	public function season_periods(){

        return $this->hasMany('App\Models\SeasonPeriod');

    }

}

?>