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

class RatesRepository {

    const WITH_CHARGING_POLICY = 1;

    public function __construct(Service $service, ExchangeRateRepository $exchangeRateRepository) {
        $this->service = $service;
        $this->exchangeRateRepository = $exchangeRateRepository;
    }

    public function getServiceByTsId($serviceTsId) {
        return Service::with('currency')->where('ts_id', $serviceTsId)->first();
    }

    public function getServiceRate($serviceOptionId, $startDate, $endDate) {
        return DB::select("select buy_price,sell_price,season_period_id,start,end from prices join season_periods on (prices.season_period_id=season_periods.id) where priceable_id=? AND season_period_id IN (select id from season_periods where start<=? AND end>=? OR start<=? AND end>=?)", [$serviceOptionId, $startDate, $startDate, $endDate, $endDate]);
    }

        public function serviceOptionsAndRates($serviceId, $startDate, $endDate)
    {
        return DB::select("select buy_price, sell_price, season_period_id, seasons.name as season_name, start, end, priceable_id as option_id, service_options.name as option_name, price_bands.id as price_band_id, price_bands.ts_id as price_band_tsid, price_bands.name as price_band_name, charging_policies.id as policy_id, charging_policies.ts_id as policy_tsid, charging_policies.name as policy_name, service_options.name as option_name, occ.name as occupancy_name, occ.id as occupancy_id, max_adults, max_children, meals.id as meal_id, meals.name as meal_name from prices join service_options on (prices.priceable_id = service_options.id) join season_periods on (prices.season_period_id=season_periods.id) join seasons on (season_periods.season_id = seasons.id) join occupancies occ on (service_options.occupancy_id=occ.id) left join ( meal_options join meals on (meal_options.meal_id = meals.id) ) on (meal_options.service_option_id = service_options.id) left join ( service_price_bands join price_bands on (service_price_bands.price_band_id = price_bands.id) ) on (service_price_bands.price_id = prices.id) left join ( service_policies join charging_policies on (service_policies.charging_policy_id = charging_policies.id)) on (service_policies.price_id = prices.id) where priceable_id IN (select id from service_options where service_id=?) AND priceable_type LIKE '%ServiceOption' AND prices.season_period_id IN (select id from season_periods where  start<=? AND end>=? OR start<=? AND end>=?) and service_options.status=?", [$serviceId, $startDate, $startDate, $endDate, $endDate, 1]);  
    }
    public function serviceExtrasAndRates($serviceId, $startDate, $endDate)
    {
        return DB::select("select buy_price, sell_price, service_extras.name as extra_name, season_period_id, seasons.name as season_name, start, end, price_bands.id as price_band_id, price_bands.ts_id as price_band_tsid, price_bands.name as price_band_name, charging_policies.id as policy_id, charging_policies.ts_id as policy_tsid, charging_policies.name as policy_name, priceable_id as extra_id, prices.id as price_id, service_extras.ts_id as extra_tsid from prices join service_extras on (prices.priceable_id = service_extras.id AND priceable_type LIKE '%ServiceExtra') join season_periods on (prices.season_period_id=season_periods.id) join seasons on (season_periods.season_id = seasons.id) left join ( service_price_bands join price_bands on (service_price_bands.price_band_id = price_bands.id) ) on (service_price_bands.price_id = prices.id) left join ( service_policies join charging_policies on (service_policies.charging_policy_id = charging_policies.id)) on (service_policies.price_id = prices.id) WHERE prices.service_id=? AND season_period_id IN (select id from season_periods where  start<=? AND end>=? OR start<=? AND end>=?) and service_extras.status=?", [$serviceId, $startDate, $startDate, $endDate, $endDate, 1]);
    }

    public function getServiceWithCurrency($serviceId) {
        return Service::with('currency')->find($serviceId);
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
        return ($totalNights > 1 && $nights == 0) ? $nights + 1 : $nights;
    }

    public function calculateTotalServiceRate($service, $startDate, $toCurrency, $quantity, $totalNights) {
        $exchangeRate = $this->exchangeRateRepository->exchangeRate($service->currency->code, $toCurrency);
        
        $carbonEnd = Carbon::parse($startDate)->addDays($totalNights);
        $endDate = $carbonEnd->format('Y-m-d');
        $actualEnd = $carbonEnd->subDay()->format('Y-m-d');
        $startDate = Carbon::parse($startDate)->format('Y-m-d');
        $serviceOptions = $this->serviceOptionsAndRates($service->id, $startDate, $actualEnd);
        
        // Charging Policy -  start
        if (self::WITH_CHARGING_POLICY == 1) {
            $pricesBreakup = $this->getPriceBreakupWithSeasonsIdWithinStartandEndDate($service->id, $startDate, $actualEnd, $quantity, $totalNights, 'ServiceRate');
            $withChargingPolicyPrices = $pricesBreakup['finalPrice'];
        }
        // Charging Policy -  End

        $respArray["GetServicesPricesAndAvailabilityResult"]["Services"]["PriceAndAvailabilityService"]["ServiceID"] = $service->ts_id;
        $respArray["GetServicesPricesAndAvailabilityResult"]["Services"]["PriceAndAvailabilityService"]["ServiceCode"] = $service->id;
        $respArray["GetServicesPricesAndAvailabilityResult"]["Warnings"] = (object) array();

        if (empty($serviceOptions) || is_null($serviceOptions)) {
            $respArray["GetServicesPricesAndAvailabilityResult"]["Errors"] = json_decode(json_encode(['Error' => [ 'Description' => 'Service not found']]));
        } else {
            $respArray["GetServicesPricesAndAvailabilityResult"]["Errors"] = (object) array();
            foreach ($serviceOptions as $key => $price) {
                
                // Charging Policy -  Start
                if (self::WITH_CHARGING_POLICY == 1) {
                    if (!empty($price->policy_id)) {                        
                        $price->buy_price = $this->applyRatesChargingPolicy($withChargingPolicyPrices, 'buy_price', $price->option_id, $price->buy_price);                        
                        $price->sell_price = $this->applyRatesChargingPolicy($withChargingPolicyPrices, 'sell_price', $price->option_id, $price->sell_price);
                    } else if (!empty($price->price_band_id)) {
//                        $price->buy_price = $this->applyPriceBandChargingPolicy($withChargingPolicyPrices, 'buy_price', $price->option_id, $price->buy_price);
//                        $price->sell_price = $this->applyPriceBandChargingPolicy($withChargingPolicyPrices, 'sell_price', $price->option_id, $price->sell_price);
                    }
                }
                // Charging Policy -  End

                if (!isset($totalBuyingPrice[$price->option_id])) {
                    $totalBuyingPrice[$price->option_id] = $totalSellingPrice[$price->option_id] = 0;
                }
                $mealPlan = ["MealPlanID" => $price->meal_id, "MealPlanName" => $price->meal_name];
                $nights = $this->getNightsCount($price->start, $price->end, $startDate, $endDate, $totalNights);
                // Charging Policy -  Start
                if (self::WITH_CHARGING_POLICY == 1) {
                    $totalBuyingPrice[$price->option_id] = ($price->buy_price);
                    $totalSellingPrice[$price->option_id] = ($price->sell_price);

                    $values = array("MaxChild" => $price->max_children, "MaxAdult" => $price->max_adults,
                        "Occupancy" => $price->occupancy_id, "Currency" => $toCurrency,
                        "TotalSellingPrice" => ceil(($totalSellingPrice[$price->option_id]) * $exchangeRate),
                        "TotalBuyingPrice" => ceil(($totalBuyingPrice[$price->option_id]) * $exchangeRate),
                        "OptionID" => $price->option_id, "ServiceOptionName" => $price->option_name
                    );
                } else {
                    $totalBuyingPrice[$price->option_id] += ($price->buy_price) * $nights;
                    $totalSellingPrice[$price->option_id] += ($price->sell_price) * $nights;

                    $values = array("MaxChild" => $price->max_children, "MaxAdult" => $price->max_adults,
                        "Occupancy" => $price->occupancy_id, "Currency" => $toCurrency,
                        "TotalSellingPrice" => ceil(($totalSellingPrice[$price->option_id]) * $exchangeRate * $quantity),
                        "TotalBuyingPrice" => ceil(($totalBuyingPrice[$price->option_id]) * $exchangeRate * $quantity),
                        "OptionID" => $price->option_id, "ServiceOptionName" => $price->option_name
                    );
                }
                // Charging Policy -  End

                $respArray["GetServicesPricesAndAvailabilityResult"]["Services"]["PriceAndAvailabilityService"]["ServiceOptions"]["PriceAndAvailabilityResponseServiceOption"][$price->option_id] = $values;
                $optionPrices[$price->option_id] = ["BuyPrice" => ($price->buy_price * $exchangeRate), "SellPrice" => ($price->sell_price * $exchangeRate), "MealPlan" => $mealPlan];
                $respArray["GetServicesPricesAndAvailabilityResult"]["Services"]["PriceAndAvailabilityService"]["ServiceOptions"]["PriceAndAvailabilityResponseServiceOption"][$price->option_id]["Prices"]["PriceAndAvailabilityResponsePricing"] = $optionPrices[$price->option_id];
            }
            $priceValues = array_values($respArray["GetServicesPricesAndAvailabilityResult"]["Services"]["PriceAndAvailabilityService"]["ServiceOptions"]["PriceAndAvailabilityResponseServiceOption"]);
            $respArray["GetServicesPricesAndAvailabilityResult"]["Services"]["PriceAndAvailabilityService"]["ServiceOptions"]["PriceAndAvailabilityResponseServiceOption"] = $priceValues;
        }

        return $respArray;
    }

    function calculateServiceExtraRate($service, $startDate, $endDate, $toCurrency, $quantity) {

        $exchangeRate = $this->exchangeRateRepository->exchangeRate($service->currency->code, $toCurrency);
        $carbonEnd = Carbon::parse($endDate);
        $nights = $carbonEnd->diffInDays(Carbon::parse($startDate));
        $actualEnd = $carbonEnd->subDay()->format('Y-m-d');
        $startDate = Carbon::parse($startDate)->format('Y-m-d');
        $serviceExtras = $this->serviceExtrasAndRates($service->id, $startDate, $actualEnd);
                     
        // Charging Policy -  start
        if (self::WITH_CHARGING_POLICY == 1) {
            $pricesBreakup = $this->getPriceBreakupWithSeasonsIdWithinStartandEndDate($service->id, $startDate, $actualEnd, $quantity, $nights, 'ServiceExtra');
            $withChargingPolicyPrices = $pricesBreakup['finalPrice'];
        }
        // Charging Policy -  End


        if (empty($serviceExtras) || is_null($serviceExtras)) {
            $responseValue = array(
                "Errors" => (object) array(),
                "ServiceId" => 0,
                "ServiceCode" => 0,
                "ServiceName" => 0,
                "ServiceTypeId" => 0,
                "ResponseList" => (object) array()
            );
            $respArray["ServiceExtrasAndPricesResponse"] = $responseValue;
        } else {
            $responseValue = array(
                "Errors" => (object) array(),
                "ServiceId" => $service->ts_id,
                "ServiceCode" => $service->id,
                "ServiceName" => $service->extra_name,
                "ServiceTypeId" => $service->service_type_id
            );
            $respArray["ServiceExtrasAndPricesResponse"] = $responseValue;

            foreach ($serviceExtras as $key => $extra) {
                
                // Charging Policy -  Start
                if (self::WITH_CHARGING_POLICY == 1) {
                    if (!empty($extra->policy_id)) {                        
                        $extra->buy_price = $this->applyRatesChargingPolicy($withChargingPolicyPrices, 'buy_price', $extra->extra_id, $extra->buy_price);
                        $extra->sell_price = $this->applyRatesChargingPolicy($withChargingPolicyPrices, 'sell_price', $extra->extra_id, $extra->sell_price);
                    } else if (!empty($price->price_band_id)) {
//                        $extra->buy_price = $this->applyPriceBandChargingPolicy($withChargingPolicyPrices, 'buy_price', $extra->extra_id, $extra->buy_price);
//                        $extra->sell_price = $this->applyPriceBandChargingPolicy($withChargingPolicyPrices, 'sell_price', $extra->extra_id, $extra->sell_price);
                    }
                }
                // Charging Policy -  End
                
                if (self::WITH_CHARGING_POLICY == 1) {
                    $value = array(
                        "ExtraMandatory" => false,
                        "OccupancyTypeID" => 0,
                        "ServiceTypeTypeID" => 1,
                        "ServiceTypeTypeName" => "Others",
                        "MaxAdults" => 100,
                        "MaxChild" => 0,
                        "MinAdults" => 0,
                        "MinChild" => 0,
                        "ChildMaxAge" => 0,
                        "ServiceExtraId" => $extra->extra_tsid,
                        "ServiceExtraCode" => $extra->extra_id,
                        "ServiceTypeExtraName" => $extra->extra_name,
                        "TOTALPRICE" => ceil($extra->sell_price * $exchangeRate)
                    );
                } else {
                    $value = array(
                        "ExtraMandatory" => false,
                        "OccupancyTypeID" => 0,
                        "ServiceTypeTypeID" => 1,
                        "ServiceTypeTypeName" => "Others",
                        "MaxAdults" => 100,
                        "MaxChild" => 0,
                        "MinAdults" => 0,
                        "MinChild" => 0,
                        "ChildMaxAge" => 0,
                        "ServiceExtraId" => $extra->extra_tsid,
                        "ServiceExtraCode" => $extra->extra_id,
                        "ServiceTypeExtraName" => $extra->extra_name,
                        "TOTALPRICE" => ceil($extra->sell_price * $exchangeRate * $nights)
                    );
                }
                
               
                $respArray["ServiceExtrasAndPricesResponse"]["ResponseList"]["ServiceExtras"][] = $value;
                $price = array(
                    "PriceId" => $extra->price_id,
                    "PriceDate" => $extra->start,
                    "CurrencyIsoCode" => $toCurrency,
                    "PriceAmount" => $extra->sell_price * $exchangeRate,
                    "BuyPrice" => $extra->buy_price * $exchangeRate,
                    "ChargingPolicyName" => $extra->policy_name
                );
                $respArray["ServiceExtrasAndPricesResponse"]["ResponseList"]["ServiceExtras"][$key]["ExtraPrices"]["ServiceExtraPrice"] = $price;
            }
        }

        return $respArray;
    }

    // charging policy - start
    function applyRatesChargingPolicy($withChargingPolicyPrices, $type, $option_id, $price) {

        switch ($type) {
            case 'buy_price':
                return $withChargingPolicyPrices['buy_price'][$option_id];
                break;
            case 'sell_price':
                return $withChargingPolicyPrices['sell_price'][$option_id];
                break;

            default:
                return $price;
                break;
        }
    }

    function getPriceBreakupWithSeasonsIdWithinStartandEndDate($serviceId, $startDate, $endDate, $quantity, $totalNights, $apiCall = 'ServiceRate') {
 
        if($apiCall == 'ServiceExtra') {            
            $serviceOptions = $this->serviceExtrasAndRates($serviceId, $startDate, $endDate);
        }  else {
            $serviceOptions = $this->serviceOptionsAndRates($serviceId, $startDate, $endDate);
        }
        
        $i = 0;
        foreach ($serviceOptions as $key => $price) {

            if($apiCall == 'ServiceExtra') {
                $price->option_id = $price->extra_id;
                $price->option_name = $price->extra_name;
            } 
           /* 
            $startDate : 2017-01-11
            $endDate : 2017-01-22
            $actualEnd : 2017-01-21
            $nights : 11


            $startDate : 2017-01-11
            $endDate : 2017-01-21
            $totalNights : 11
            * 
                
            Service Extras ->   "SERVICEID" => 1475,
                                "FROMDATE" => "2017-01-11",
                                "TODATE" => "2017-01-22",
            
                [Season 03] => 2016-12-16 to 2017-01-15
            
Check IN     1 night = date  : 2017-01-11 (11 Jan) - day/night
             2 night = date  : 2017-01-12 (12 Jan)
             3 night = date  : 2017-01-13 (13 Jan)
             4 night = date  : 2017-01-14 (14 Jan)
             5 night = date  : 2017-01-15 (15 Jan)
             
            
             
                 [Season 04] => 2017-01-16 to 2017-02-28
             
             6 night = date  : 2017-01-16 (16 Jan)
             7 night = date  : 2017-01-17 (17 Jan)
             8 night = date  : 2017-01-18 (18 Jan)
             9 night = date  : 2017-01-19 (19 Jan)
             10 night = date : 2017-01-20 (20 Jan)
             11 night = date : 2017-01-21 (21 Jan)
Check Out    12       = date : 2017-01-22 (22 Jan)  - day     
             
            * 
            * 
            * 
            * 
            * 
            *   start date 04/08/2015 (08 April) for 11 nights
            
            
            Service Options -> 'SERVICEIDs' => '26666', 'START_DATE' => '04/07/2015'
            [Season 02] => 2015-01-08 to 2015-04-14
            
Checkin      1 night = date  : 2015-04-08 (8 April) - day/night
             2 night = date  : 2015-04-09 (9 April) - day/night
             3 night = date  : 2015-04-10 (10 April) - day/night
             4 night = date  : 2015-04-11 (11 April) - day/night
             5 night = date  : 2015-04-12 (12 April) - day/night
             6 night = date  : 2015-04-13 (13 April) - day/night
             7 night = date  : 2015-04-14 (14 April) - day/night
             
             
             
             
             
                [Season 01] => 2015-04-15 to 2015-09-29

             8 night = date  : 2015-04-15 (15 April) - day/night
             9 night = date  : 2015-04-16 (16 April) - day/night
             10 night = date : 2015-04-17 (17 April) - day/night
             11 night = date : 2015-04-18 (18 April) - day/night
Checkout              = date : 2015-04-19 (19 April) - day
            * 
            
                */
            
            
            
     
            if (!isset($priceWithChargingPolicy['finalPrice']['buy_price'][$price->option_id]))
            $priceWithChargingPolicy['finalPrice']['buy_price'][$price->option_id] = 0;
            if (!isset($priceWithChargingPolicy['finalPrice']['sell_price'][$price->option_id]))
            $priceWithChargingPolicy['finalPrice']['sell_price'][$price->option_id] = 0;       
                   

     
            if (!empty($price->policy_id)) {
                $charging_policy_criteria = 'charging_policy';
                $charging_policy = DB::select("select sp.id as service_policy_id, sp.charging_policy_id, cp.name as charging_policy_name, cp.charging_duration, cp.day_duration,cp.room_based from service_policies sp, charging_policies cp, prices p where p.season_period_id = ? and p.priceable_id = ? and sp.price_id = p.id and sp.charging_policy_id = ? and  sp.charging_policy_id = cp.id", [$price->season_period_id, $price->option_id, $price->policy_id]);
            } else if (!empty($price->price_band_id)) {
                $charging_policy_criteria = 'price_band';
                $charging_policy = DB::select("select id as price_band_id, name, min, max from price_bands where id = ? ", [$price->price_band_id]);
            }

            

            if (!isset($priceWithChargingPolicy[$price->season_name])) {
                
                $i = 0;
                
                $nights = $this->getNightsCount($price->start, $price->end, $startDate, $endDate, $totalNights)+1;    
                $priceWithChargingPolicy[$price->season_name] = array(
                    'season_period_id' => $price->season_period_id,
                    'season_name' => $price->season_name,
                    'season_period' => $price->start . ' to ' . $price->end,
                    'number_of_nights' => $nights
                );
                
            }
            
            $buy_price = $this->getFinalRatesWithChargingPolicy($price->buy_price, $charging_policy, $quantity, $nights, $charging_policy_criteria);
            $sell_price = $this->getFinalRatesWithChargingPolicy($price->sell_price, $charging_policy, $quantity, $nights, $charging_policy_criteria);

            $priceWithChargingPolicy[$price->season_name]['options'][$i] = array(
                'option_id' => $price->option_id,
                'option_name' => $price->option_name,
                'with_out_policy_buy_price' => $price->buy_price,
                'with_out_policy_sell_price' => $price->sell_price,
                'charging_policy_criteria' => $charging_policy_criteria,
                'charging_policy' => $charging_policy,
                'with_policy_buy_price' => $buy_price,
                'with_policy_sell_price' => $sell_price
            );

            $priceWithChargingPolicy['finalPrice']['buy_price'][$price->option_id] += ($buy_price);
            $priceWithChargingPolicy['finalPrice']['sell_price'][$price->option_id] += ($sell_price);

            $i++;
        }
//print_r($priceWithChargingPolicy) ;
        return isset($priceWithChargingPolicy) ? $priceWithChargingPolicy : false;
    }

    function getFinalRatesWithChargingPolicy($price, $charging_policy, $quantity, $nights, $charging_policy_criteria) {

        if ($charging_policy_criteria == 'charging_policy' && is_array($charging_policy) && isset($charging_policy[0])) {

            $charging_policy_name = strtolower($charging_policy[0]->charging_policy_name);
            $is_charging_policy_room_based = strtolower($charging_policy[0]->room_based); // 1= yes
            $charging_policy_day_duration = strtolower($charging_policy[0]->day_duration); // 1= yes
            $charging_policy_name = trim($charging_policy_name);

            if ($is_charging_policy_room_based == '1') { // unit/room based
                if ($charging_policy_day_duration == '1') { // per unit/room per day/night
                    return $price * $nights;
                } else if ($charging_policy_day_duration > '0') { // per unit/room per N day/night
                    $nnights = ceil($nights / $charging_policy_day_duration);
                    return $price * $nnights;
                }
            } else if ($is_charging_policy_room_based == '0') { // person based 
                if ($charging_policy_day_duration == '1') {  // per person per day/night
                    return ($price * $quantity * $nights);
                } else if ($charging_policy_day_duration > '0') {  // per person per  N day/night     
                    $nnights = ceil($nights / $charging_policy_day_duration);
                    return $price * $quantity * $nnights;
                }
            }
        } else if ($charging_policy_criteria == 'price_band') {
            $charging_policy_name = $charging_policy[0]->name;
            $min = $charging_policy[0]->min;
            $max = $charging_policy[0]->max;
            $nquantity = ceil($quantity / $max);
            return $price * $nquantity;
        }
        return $price;
    }

    function applyPriceBandChargingPolicy($withChargingPolicyPrices, $type, $option_id, $price) {
        return false;
    }

    // charging policy - end
}
