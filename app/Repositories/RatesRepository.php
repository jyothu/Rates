<?php
namespace App\Repositories;

use App\Models\Service;
use App\Models\Price;
use DB;
use Carbon\Carbon;
use DateTime;
use DateInterval;
use DatePeriod;
use App\Service\ConvertCurrency;
use SoapBox\Formatter\Formatter;

class RatesRepository
{
    public function __construct(Service $service, ConvertCurrency $convertCurrency)
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
        return DB::select("select buy_price, sell_price, season_period_id, start, end, priceable_id as option_id, service_options.name as option_name, occ.name as occupancy_name, occ.id as occupancy_id, max_adults, min_adults, services.id as service_id, services.name as service_name from prices join service_options on (prices.priceable_id = service_options.id) join season_periods on (prices.season_period_id=season_periods.id) join occupancies occ on (service_options.occupancy_id=occ.id) where priceable_id IN (select id from service_options where service_id=?) AND season_period_id IN (select id from season_periods where  start<=? AND end>=? OR start<=? AND end>=?)", [$serviceId, $startDate, $startDate, $endDate, $endDate]);
    }

    public function getService($serviceId)
    {
       // return DB::select("select s.id,s.name,code from services as s join currencies on (s.currency_id = currencies.id) WHERE s.id=?", [$serviceId]);
       return Service::with('currencies')->find( $serviceId );
    }

    public function calculateTotalServiceRate($serviceId, $startDate, $endDate, $currency)
    {
        
        $service = getService( $serviceId );
        $exchangeRate = $convertCurrency->exchangeRate($currency, $service->currency->code);

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

        $respArray["GetServicesPricesAndAvailabilityResult"]["Errors"] = "";
        $respArray["GetServicesPricesAndAvailabilityResult"]["Warnings"] = "";
        $respArray["GetServicesPricesAndAvailabilityResult"]["Services"]["PriceAndAvailabilityService"]["ServiceID"] = $serviceId;
        $respArray["GetServicesPricesAndAvailabilityResult"]["Services"]["PriceAndAvailabilityService"]["ServiceCode"] = $serviceId;
        
        foreach ($holder as $key => $value) {
            $values = array("MinAdult" => $value["min_adults"],
                "MaxAdult" =>  $value["max_adults"],
                "Occupancy" => $value["occupancy_id"],
                "Currency" => $currency,
                "TotalSellingPrice" => $value["TotalSellingPrice"]*$exchangeRate, 
                "TotalBuyingPrice" => $value["TotalBuyingPrice"]*$exchangeRate,
                "OptionID" => $value["option_id"],
                "ServiceOptionName" => $value["option_name"]
            );
            $respArray["GetServicesPricesAndAvailabilityResult"]["Services"]["PriceAndAvailabilityService"]["ServiceOptions"]["PriceAndAvailabilityResponseServiceOption"][] = $values
        }

        $formatter = Formatter::make(["GetServicesPricesAndAvailabilityResponse" => $respArray], Formatter::ARR);
        return $formatter->toXml();

}
