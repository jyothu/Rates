<?php 

namespace Enchanting\MigrateTs\Models;

class Supplier extends \Illuminate\Database\Eloquent\Model {
    
    protected $fillable = array('name', 'description', 'region_id', 'status', 'id');

    public function services(){

        return $this->hasMany('Enchanting\MigrateTs\Models\Service');

    }

    public function region(){

        return $this->belongsTo('Enchanting\MigrateTs\Models\Region');

    }

}

?>