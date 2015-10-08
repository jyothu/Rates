<?php

namespace App\Models;

class Season extends \Eloquent
{
	protected $fillable = array('name', 'contract_period_id', 'ts_id', 'status');

    public function contractPeriod(){
        return $this->belongsTo('App\Models\ContractPeriod');
    }

	public function seasonPeriods(){
        return $this->hasMany('App\Models\SeasonPeriod');
    }

}
