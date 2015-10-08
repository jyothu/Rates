<?php
namespace App\Repositories;

use App\Models\Service;
use App\Models\ServiceOption;
use App\Models\Price;
use DB;
use Carbon\Carbon;
use DateTime;
use DateInterval;
use DatePeriod;
use SoapBox\Formatter\Formatter;

class RatesRepository
{
    public function __construct(Service $service, ExchangeRateRepository $exchangeRateRepository)
    {
        $this->service = $service;
        $this->exchangeRateRepository = $exchangeRateRepository;
    }

    public function getServiceByTsId($serviceTsId)
    {
        return Service::where('ts_id', $serviceTsId)->first();
    }

    public function getServiceRate($serviceOptionId, $startDate, $endDate)
    {
        return DB::select("select buy_price,sell_price,season_period_id,start,end from prices join season_periods on (prices.season_period_id=season_periods.id) where priceable_id=? AND season_period_id IN (select id from season_periods where start<=? AND end>=? OR start<=? AND end>=?)", [$serviceOptionId, $startDate, $startDate, $endDate, $endDate]);
    }

    public function serviceOptionsAndRates($serviceId, $startDate, $endDate)
    {
        return DB::select("select buy_price, sell_price, season_period_id, start, end, priceable_id as option_id, service_options.name as option_name, occ.name as occupancy_name, occ.id as occupancy_id, max_adults, max_children, meals.id as meal_id, meals.name as meal_name from prices join service_options on (prices.priceable_id = service_options.id) join meal_options on (meal_options.service_option_id = service_options.id) join meals on (meal_options.meal_id = meals.id) join season_periods on (prices.season_period_id=season_periods.id) join occupancies occ on (service_options.occupancy_id=occ.id) where priceable_id IN (select id from service_options where service_id=?) AND priceable_type LIKE '%ServiceOption' AND season_period_id IN (select id from season_periods where  start<=? AND end>=? OR start<=? AND end>=?) and service_options.status=?", [$serviceId, $startDate, $startDate, $endDate, $endDate, 1]);  
    }

    public function getServiceWithCurrency($serviceId)
    {
       return Service::with('currency')->find( $serviceId );
    }

    public function getNightsCount($seasonStart, $seasonEnd, $dayStart, $dayEnd, $totalNights) {
        $seasonStart = Carbon::parse($seasonStart);
        $seasonEnd = Carbon::parse($seasonEnd);
        $dayStart = Carbon::parse($dayStart);
        $dayEnd = Carbon::parse($dayEnd);

        if ($dayStart > $seasonEnd || $seasonStart > $dayEnd || $seasonEnd < $seasonStart || $dayEnd < $dayStart) {
            return 0;
        }

        $start = $seasonStart < $dayStart ? $dayStart : $seasonStart;
        $end = $seasonEnd < $dayEnd ? $seasonEnd : $dayEnd;
        $nights = $end->diffInDays($start);
        return ($totalNights > 1 && $nights == 0) ? $nights+1 : $nights;
    }   

    public function calculateTotalServiceRate($serviceId, $startDate, $endDate, $currency, $quantity, $totalNights)
    {        

        $service = $this->getServiceWithCurrency($serviceId);
        $exchangeRate = $this->exchangeRateRepository->exchangeRate($service->currency->code, $currency);
        $carbonEnd = Carbon::parse($endDate);
        $actualEnd = $carbonEnd->subDay()->format('Y-m-d');
        $serviceOptions = $this->serviceOptionsAndRates($serviceId, $startDate, $actualEnd);
      
        $respArray["GetServicesPricesAndAvailabilityResult"]["Services"]["PriceAndAvailabilityService"]["ServiceID"] = $service->ts_id;
        $respArray["GetServicesPricesAndAvailabilityResult"]["Services"]["PriceAndAvailabilityService"]["ServiceCode"] = $serviceId;
        $respArray["GetServicesPricesAndAvailabilityResult"]["Warnings"] = (object) array();
        
        if (empty($serviceOptions) || is_null($serviceOptions)) {
            $respArray["GetServicesPricesAndAvailabilityResult"]["Errors"] = json_decode(json_encode(['Error' => [ 'Description' => 'Service not found']]));
        } else {
            $respArray["GetServicesPricesAndAvailabilityResult"]["Errors"] = (object) array();
            foreach ($serviceOptions as $key=>$price) {
                if (!isset($totalBuyingPrice[$price->option_id])){
                    $totalBuyingPrice[$price->option_id] = $totalSellingPrice[$price->option_id] = 0;
                }
                $mealPlan = ["MealPlanID" => $price->meal_id, "MealPlanName" =>$price->meal_name];
                $nights = $this->getNightsCount($price->start, $price->end, $startDate, $endDate, $totalNights);
                $totalBuyingPrice[$price->option_id] += ($price->buy_price)*$nights;
                $totalSellingPrice[$price->option_id] += ($price->sell_price)*$nights;

                 $values = array("MaxChild" => $price->max_children, "MaxAdult" =>  $price->max_adults,
                    "Occupancy" => $price->occupancy_id, "Currency" => $currency,
                    "TotalSellingPrice" => ceil(($totalSellingPrice[$price->option_id])*$exchangeRate*$quantity),
                    "TotalBuyingPrice" => ceil(($totalBuyingPrice[$price->option_id])*$exchangeRate*$quantity),
                    "OptionID" => $price->option_id, "ServiceOptionName" => $price->option_name
                );

                $respArray["GetServicesPricesAndAvailabilityResult"]["Services"]["PriceAndAvailabilityService"]["ServiceOptions"]["PriceAndAvailabilityResponseServiceOption"][$price->option_id] = $values;
                $optionPrices[$price->option_id] = ["BuyPrice" => ($price->buy_price*$exchangeRate), "SellPrice" => ($price->sell_price*$exchangeRate), "MealPlan" => $mealPlan];
                $respArray["GetServicesPricesAndAvailabilityResult"]["Services"]["PriceAndAvailabilityService"]["ServiceOptions"]["PriceAndAvailabilityResponseServiceOption"][$price->option_id]["Prices"]["PriceAndAvailabilityResponsePricing"] = $optionPrices[$price->option_id];
            }
            $priceValues = array_values($respArray["GetServicesPricesAndAvailabilityResult"]["Services"]["PriceAndAvailabilityService"]["ServiceOptions"]["PriceAndAvailabilityResponseServiceOption"]);
            $respArray["GetServicesPricesAndAvailabilityResult"]["Services"]["PriceAndAvailabilityService"]["ServiceOptions"]["PriceAndAvailabilityResponseServiceOption"] = $priceValues;
        }

        return $respArray;
    }
}
