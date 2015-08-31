<?php

namespace App\Models;

class Price extends \Eloquent
{
    
    protected $fillable = array('id', 'season_period_id', 'buy_price', 'sell_price', 'has_details', 'status');

    public function priceable()
    {

        return $this->morphTo();
    }
    
    public function seasonPeriod()
    {

        return $this->belongsTo('App\Models\SeasonPeriod');

    }

    public static function bySeason($start, $end)
    {
        return static::join('season_periods', 'prices.season_period_id', '=', 'season_periods.id')
            ->where(function ($startQuery) use ($start) {
                $startQuery->where('start', '<=', $start)->where('end', '>=', $start);
            })
            ->orWhere(function ($endQuery) use ($end) {
                $endQuery->where('start', '<=', $end)->where('end', '>=', $end);
            });
    
    }
}
