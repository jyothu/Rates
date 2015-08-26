<?php 

namespace Enchanting\MigrateTs\Models;

class ServiceExtra extends \Illuminate\Database\Eloquent\Model {
    
    protected $fillable = array('name', 'service_option_id', 'mandatory', 'status', 'id');

    public function prices(){

        return $this->morphMany('Enchanting\MigrateTs\Models\Price', 'priceable');
    }

    public function service_option(){

        return $this->belongsTo('Enchanting\MigrateTs\Models\ServiceOption');

    }

}

?>