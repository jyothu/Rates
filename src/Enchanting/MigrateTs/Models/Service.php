<?php 

namespace Enchanting\MigrateTs\Models;

class Service extends \Illuminate\Database\Eloquent\Model {
    
    protected $fillable = array('id', 'short_name', 'name', 'description', 'service_type_id', 'region_id', 'supplier_id', 'currency_id', 'status');

    public function seasons(){

        return $this->hasMany('Enchanting\MigrateTs\Models\Season');
    
    }

    public function service_options(){

        return $this->hasMany('Enchanting\MigrateTs\Models\ServiceOption');
    
    }

    public function service_type(){

        return $this->belongsTo('Enchanting\MigrateTs\Models\ServiceType');
    
    }

    public function region(){

        return $this->belongsTo('Enchanting\MigrateTs\Models\Region');
    
    }

    public function supplier(){

        return $this->belongsTo('Enchanting\MigrateTs\Models\Supplier');
    
    }

    public function currency(){

        return $this->belongsTo('Enchanting\MigrateTs\Models\Currency');
    
    }

}

?>