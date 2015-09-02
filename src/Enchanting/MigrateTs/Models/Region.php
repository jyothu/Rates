<?php 

namespace App\Models;

class Region extends \Illuminate\Database\Eloquent\Model {
    
    protected $fillable = array('id', 'name', 'parent_id');


    public function services(){

        return $this->hasMany('App\Models\Service');

    }

    public function suppliers(){

        return $this->hasMany('App\Models\Supplier');

    }

    public function country(){

        return $this->belongsTo('App\Models\Region', 'parent_id');

    }

    public function regions(){

        return $this->hasMany('App\Models\Region', 'parent_id');

    }

}

?>
