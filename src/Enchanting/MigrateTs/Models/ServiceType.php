<?php 

namespace App\Models;

class ServiceType extends \Illuminate\Database\Eloquent\Model {
    
    protected $fillable = array('name', 'id');

    public function services(){

        return $this->hasMany('App\Models\Service');

    }

}

?>
