<?php 

namespace App\Models;

class Service extends \Illuminate\Database\Eloquent\Model {
    
    protected $fillable = array('id', 'short_name', 'name', 'description', 'service_type_id', 'region_id', 'supplier_id', 'currency_id', 'status');

    public function seasons(){

        return $this->hasMany('App\Models\Season');
    
    }

    public function prices(){

        return $this->hasMany('App\Models\Price');
    
    }

    public function service_options(){

        return $this->hasMany('App\Models\ServiceOption');
    
    }

    public function service_type(){

        return $this->belongsTo('App\Models\ServiceType');
    
    }

    public function region(){

        return $this->belongsTo('App\Models\Region');
    
    }

    public function supplier(){

        return $this->belongsTo('App\Models\Supplier');
    
    }

    public function currency(){

        return $this->belongsTo('App\Models\Currency');
    
    }

}

?>