<?php
namespace App\Repositories;

use App\Models\Service;
use App\Models\Price;
use DB;

class RatesRepository
{
    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    public function getServiceById($serviceId)
    {
        return Service::find($serviceId);
    }

    public function getServiceRate($serviceOptionId, $startDate, $endDate)
    {
        return DB::select("select buy_price,sell_price,season_period_id,start,end from prices join season_periods on (prices.season_period_id=season_periods.id) where priceable_id=? AND season_period_id IN (select id from season_periods where start<=? AND end>=? OR start<=? AND end>=?)", [$serviceOptionId, $startDate, $startDate, $endDate, $endDate]);
    }
}
