<?php 
namespace App\Models;

class Contract extends \Eloquent
{
	protected $fillable = array('ts_id', 'name', 'service_id', 'status');

    public function service(){
        return $this->belongsTo('App\Models\Service');
    }

	public function contractPeriods(){
        return $this->hasMany('App\Models\ContractPeriod');
    }

}