<?php 

namespace App\Models;

class Price extends \Illuminate\Database\Eloquent\Model {
    
    protected $fillable = array('service_id', 'season_period_id', 'buy_price', 'sell_price', 'has_details', 'status');

    public function priceable(){

        return $this->morphTo();
    }
    
    public function seasonPeriod(){

        return $this->belongsTo('App\Models\SeasonPeriod');

    }

    public function service(){

        return $this->belongsTo('App\Models\Service');

    }

}

?>