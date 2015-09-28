<?php 

namespace App\Models;

class Contract extends \Illuminate\Database\Eloquent\Model {

	protected $fillable = array('ts_id', 'name', 'service_id', 'status');

    public function service(){

        return $this->belongsTo('App\Models\Service');

    }

	public function contractPeriods(){

        return $this->hasMany('App\Models\ContractPeriod');

    }

}

?>