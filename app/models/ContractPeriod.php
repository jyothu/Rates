<?php 
namespace App\Models;

class ContractPeriod extends \Eloquent
{
	protected $fillable = array('contract_id', 'ts_id', 'name', 'start', 'end', 'status');

    public function contract(){
        return $this->belongsTo('App\Models\Contract');
    }

	public function seasons(){
        return $this->hasMany('App\Models\Season');
    }

}