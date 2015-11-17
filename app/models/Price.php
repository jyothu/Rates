<?php

namespace App\Models;

class Price extends \Eloquent
{
    
    protected $fillable = array('priceable_id', 'priceable_type', 'season_period_id', 'service_id', 'buy_price', 'sell_price', 'has_details', 'status');

    public function priceable(){
        return $this->morphTo();
    }

    public function servicePolicy() {
        return $this->hasOne('App\Models\ServicePolicy');
    }

    public function seasonPeriod(){
        return $this->belongsTo('App\Models\SeasonPeriod');
    }

    public function service(){
        return $this->belongsTo('App\Models\Service');
    }

    public function weekPrices() {
        return $this->hasMany('App\Models\WeekPrice');
    }
}
