<?php 

namespace Enchanting\MigrateTs\Models;

class ServiceType extends \Illuminate\Database\Eloquent\Model {
    
    protected $fillable = array('name', 'id');

    public function services(){

        return $this->hasMany('Enchanting\MigrateTs\Models\Service');

    }

}

?>
