<?php 

namespace App\Models;

class Supplier extends \Illuminate\Database\Eloquent\Model {
    
    protected $fillable = array('name', 'description', 'region_id', 'status', 'id');

    public function services(){

        return $this->hasMany('App\Models\Service');

    }

    public function region(){

        return $this->belongsTo('App\Models\Region');

    }

}

?>