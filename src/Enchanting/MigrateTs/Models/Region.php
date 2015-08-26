<?php 

namespace Enchanting\MigrateTs\Models;

class Region extends \Illuminate\Database\Eloquent\Model {
    
    protected $fillable = array('id', 'name', 'parent_id');


    public function services(){

        return $this->hasMany('Enchanting\MigrateTs\Models\Service');

    }

    public function suppliers(){

        return $this->hasMany('Enchanting\MigrateTs\Models\Supplier');

    }

    public function country(){

        return $this->belongsTo('Enchanting\MigrateTs\Models\Region', 'parent_id');

    }

    public function regions(){

        return $this->hasMany('Enchanting\MigrateTs\Models\Region', 'parent_id');

    }

}

?>
