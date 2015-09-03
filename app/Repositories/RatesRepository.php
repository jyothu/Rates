<?php
namespace App\Repositories;

use App\Models\Service;
use App\Models\Price;
use DB;
use Carbon\Carbon;
use DateTime;
use DateInterval;
use DatePeriod;

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

    public function getAllServiceRate($serviceId, $startDate, $endDate)
    {
        return DB::select("select buy_price,sell_price,season_period_id,start,end,priceable_id as option_id,name from prices join service_options on (prices.priceable_id = service_options.id) join season_periods on (prices.season_period_id=season_periods.id) where priceable_id IN (select id from service_options where service_id = ?) AND season_period_id IN (select id from season_periods where start<=? AND end>=? OR start<=? AND end>=?)", [$serviceId, $startDate, $startDate, $endDate, $endDate]);
    }

    public function calculateTotalServiceRate($serviceId, $startDate, $endDate)
    {
        $carbonEnd = Carbon::parse($endDate);

        $actualEnd = $carbonEnd->subDay()->format('Y-m-d');
        $holder = [];

        $startObj = new DateTime($startDate);
        $endObj = new DateTime($endDate);
        $interval = DateInterval::createFromDateString('1 day');
        $period = new DatePeriod($startObj, $interval, $endObj);

        $prices = $this->getAllServiceRate($serviceId, $startDate, $actualEnd);
        
        foreach ($prices as $price) {
            $holder[$price->name]['seasons'][] = $price;
        }
        foreach ($holder as $key => $options) {
            $totalBuyingPrice = $totalSellingPrice = 0;
            if (count($options['seasons']) == 1) {
                $nights = Carbon::parse($endDate)->diffInDays(Carbon::parse($startDate));
                $totalBuyingPrice = ($options['seasons'][0]->buy_price)*($nights);
                $totalSellingPrice = ($options['seasons'][0]->sell_price)*($nights);
            
            } else {
                $totalBuyingPrice = $totalSellingPrice = 0;

                foreach ($options['seasons'] as $option) {
                    $carbonStartDate = Carbon::parse($option->start);
                    $carbonEndDate = Carbon::parse($option->end);
                    
                    foreach ($period as $date) {
                        if (Carbon::parse($date->format('Y-m-d'))->between($carbonStartDate, $carbonEndDate)) {
                            $totalBuyingPrice += $option->buy_price;
                            $totalSellingPrice += $option->sell_price;
                        }
                    }
                }
            }
            $holder[$key]['totalBuyingPrice'] = $totalBuyingPrice;
            $holder[$key]['totalSellingPrice'] = $totalSellingPrice;
        }
        return $holder;
    }
}
