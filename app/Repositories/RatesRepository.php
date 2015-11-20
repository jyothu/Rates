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

    public function serviceOptionsAndRates($serviceId, $startDate, $endDate, $rooms)
    {
        $occupancyIds = implode(array_keys($rooms), ",");
        $noOfPeople = array_reduce(array_values($rooms), function($total=0, $ra) { return $total += $ra["NO_OF_PASSENGERS"]; });        
        $serviceOptionsAndRates = DB::select("select buy_price, sell_price, monday, tuesday, wednesday, thursday, friday, saturday, sunday, season_period_id, start, end, service_options.ts_id as ts_option_id, priceable_id as option_id, service_options.name as option_name, price_bands.id as price_band_id, price_bands.min as price_band_min, price_bands.max as price_band_max, charging_policies.id as policy_id, charging_policies.ts_id as policy_tsid, charging_policies.name as policy_name, charging_policies.room_based as room_based, charging_policies.day_duration as day_duration, occ.name as occupancy_name, occ.id as occupancy_id, max_adults, max_children, meals.id as meal_id, meals.name as meal_name from prices join service_options on (prices.priceable_id = service_options.id) join season_periods on (prices.season_period_id=season_periods.id) join occupancies occ on (service_options.occupancy_id=occ.id) join service_policies on (service_policies.price_id = prices.id) join charging_policies on (service_policies.charging_policy_id = charging_policies.id) left join ( meal_options join meals on (meal_options.meal_id = meals.id) ) on (meal_options.service_option_id = service_options.id) right join ( policy_price_bands join price_bands on (policy_price_bands.price_band_id = price_bands.id AND price_bands.min<=? AND price_bands.max>=?) ) on (policy_price_bands.service_policy_id = service_policies.id) left join week_prices on (prices.id = week_prices.price_id) where priceable_id IN (select id from service_options where service_id=?) AND priceable_type LIKE '%ServiceOption' AND prices.season_period_id IN (select id from season_periods where  start<=? AND end>=? OR start<=? AND end>=?) and occ.id IN ($occupancyIds) and service_options.status=? group by option_id", [$noOfPeople, $noOfPeople, $serviceId, $startDate, $startDate, $endDate, $endDate, 1]); 
              
        if(count($serviceOptionsAndRates) == 0) { // if Record doesn't exits with respect to price band
            $serviceOptionsAndRates = DB::select("select prices.id as prices_id,buy_price, sell_price, monday, tuesday, wednesday, thursday, friday, saturday, sunday, season_period_id, start, end, service_options.ts_id as ts_option_id, priceable_id as option_id, service_options.name as option_name, charging_policies.id as policy_id, charging_policies.ts_id as policy_tsid, charging_policies.name as policy_name, charging_policies.room_based as room_based, charging_policies.day_duration as day_duration, occ.name as occupancy_name, occ.id as occupancy_id, max_adults, max_children, meals.id as meal_id, meals.name as meal_name from prices join service_options on (prices.priceable_id = service_options.id) join season_periods on (prices.season_period_id=season_periods.id) join occupancies occ on (service_options.occupancy_id=occ.id) join service_policies on (service_policies.price_id = prices.id) join charging_policies on (service_policies.charging_policy_id = charging_policies.id) left join ( meal_options join meals on (meal_options.meal_id = meals.id) ) on (meal_options.service_option_id = service_options.id) left join week_prices on (prices.id = week_prices.price_id) where priceable_id IN (select id from service_options where service_id=?) AND priceable_type LIKE '%ServiceOption' AND prices.season_period_id IN (select id from season_periods where  start<=? AND end>=? OR start<=? AND end>=?) and occ.id IN ($occupancyIds) and service_options.status=? group by option_id", [ $serviceId, $startDate, $startDate, $endDate, $endDate, 1]); 
        }
        
        return $serviceOptionsAndRates;
    }

    public function serviceExtrasAndRates($serviceId, $startDate, $endDate)
    {
        return DB::select("select buy_price, sell_price, service_extras.name as extra_name, season_period_id, start, end, charging_policies.id as policy_id, charging_policies.ts_id as policy_tsid, charging_policies.name as policy_name, charging_policies.room_based as room_based, charging_policies.day_duration as day_duration, priceable_id as extra_id, prices.id as price_id, service_extras.ts_id as extra_tsid from prices join service_extras on (prices.priceable_id = service_extras.id AND priceable_type LIKE '%ServiceExtra') join season_periods on (prices.season_period_id=season_periods.id) join service_policies on (service_policies.price_id = prices.id) join charging_policies on (service_policies.charging_policy_id = charging_policies.id) left join week_prices on (prices.id = week_prices.price_id) WHERE prices.service_id=? AND season_period_id IN (select id from season_periods where  start<=? AND end>=? OR start<=? AND end>=?) and service_extras.status=? group by extra_id", [$serviceId, $startDate, $startDate, $endDate, $endDate, 1]);
    }

    public function getServiceWithCurrency($serviceId) {
        return Service::with('currency')->find($serviceId);
    }

    // Calculating multiplicand with respect to either charging policy or Price bands.
    public function multiplicandByChargingPolicy($policyObj, $startDate, $endDate, $quantity, $noOfPeople, $totalNights) {
        $multiplicand = 1;
         if (isset($policyObj->price_band_id) && !empty($policyObj->price_band_id)) {
            $multiplicand *= $noOfPeople;
        } else if (isset($policyObj->policy_id) && !empty($policyObj->policy_id)) {
            if ($policyObj->policy_name != "Fast Build") {
                $isRoomBased = $policyObj->room_based; // 1= yes
                $dayDuration = $policyObj->day_duration; // 1= yes
                $nights = $this->getNightsCount($policyObj->start, $policyObj->end, $startDate, $endDate, $totalNights);
                $nights += (preg_match("/day/i",$policyObj->policy_name) ? 1 : ($nights == 0 ? 1 : 0));
                if ($isRoomBased == '1') { // unit/room based
                    if ($dayDuration <= '1') { // per unit/room per day/night
                        $multiplicand *= $nights*$quantity;
                    } else { // per unit/room per N day/night
                        $nnights = ceil($nights / $dayDuration);
                        $multiplicand *= $nnights*$quantity;
                        $chargingPolicyMultiplicand['nnights'] = $nnights;
                        $chargingPolicyMultiplicand['nights'] = $nights;
                        $chargingPolicyMultiplicand['dayDuration'] = $dayDuration;
                    }
                } else { // person based 
                    if ($dayDuration <= '1') {  // per person per day/night
                        $multiplicand *= $noOfPeople*$nights;
                    } else {  // per person per  N day/night     
                        $nnights = ceil($nights / $dayDuration);
                        $multiplicand *= $noOfPeople*$nnights;
                        $chargingPolicyMultiplicand['nnights'] = $nnights;
                        $chargingPolicyMultiplicand['nights'] = $nights;
                        $chargingPolicyMultiplicand['dayDuration'] = $dayDuration;
                    }
                }
                $chargingPolicyMultiplicand['multiplicand'] = $multiplicand;
            }
        }
        
        //return $multiplicand;
        return $chargingPolicyMultiplicand;
    }

    public function getNightsCount($seasonStart, $seasonEnd, $dayStart, $dayEnd, $totalNights) {
        $seasonStart = Carbon::parse($seasonStart);
        $seasonEnd = Carbon::parse($seasonEnd)->addDay();
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
    
    public function getPriceByConsideringWeekDay($service_id,$startDate, $season_period_id, $option_id, $totalNights, $exchangeRate, $chargingPolicyMultiplicand) {
                      
        $buy_price = 0;
        $sell_price = 0;
        $weekDayPriceArr = array();

        for($count = 0; $count < $totalNights; $count++ ) {            
            if($count == 0) {
                $startDate_day = Carbon::parse($startDate)->format('l'); 
            } else {               
                $startDate = Carbon::parse($startDate)->addDay();
                $startDate_day = $startDate->format('l'); 
            }
            $weekDayPriceObj = DB::select("select p.buy_price, p.sell_price from prices p, week_prices wp where p.service_id = ".$service_id. " and p.id = wp.price_id and wp.".strtolower($startDate_day)." = 1 and p.season_period_id = ".$season_period_id. " and p.priceable_id=".$option_id );      
            if(!empty($weekDayPriceObj)) {                
                $buy_price += ceil($weekDayPriceObj[0]->buy_price*$exchangeRate);
                $sell_price += ceil($weekDayPriceObj[0]->sell_price*$exchangeRate);
            }
            if(isset($chargingPolicyMultiplicand['dayDuration']) && $chargingPolicyMultiplicand['dayDuration'] > 1) {
                $totalNights -= $chargingPolicyMultiplicand['dayDuration']-1;
                $startDate = Carbon::parse($startDate)->addWeekdays(($chargingPolicyMultiplicand['dayDuration']-1));
            }
        }
          
      
        if($buy_price > 0) {
            $weekDayPriceArr['buy_price'] = $buy_price;
            $weekDayPriceArr['sell_price'] = $sell_price;
            return $weekDayPriceArr;
        }
        return false;
    }

    public function calculateTotalServiceRate($service, $startDate, $toCurrency, $rooms, $totalNights) {
        
        if(!empty($toCurrency)) {
            $exchangeRate = $this->exchangeRateRepository->exchangeRate($service->currency->code, $toCurrency);
        } else {
            $exchangeRate = 1;
            $toCurrency = $service->currency->code;
        } 
        $carbonEnd = Carbon::parse($startDate)->addDays($totalNights);
        $endDate = $carbonEnd->format('Y-m-d');
        $actualEnd = $carbonEnd->subDay()->format('Y-m-d');
        $startDate = Carbon::parse($startDate)->format('Y-m-d');
        $serviceOptions = $this->serviceOptionsAndRates($service->id, $startDate, $actualEnd, $rooms);
        
        $respArray["GetServicesPricesAndAvailabilityResult"]["Services"]["PriceAndAvailabilityService"]["ServiceID"] = $service->ts_id;
        $respArray["GetServicesPricesAndAvailabilityResult"]["Services"]["PriceAndAvailabilityService"]["ServiceCode"] = $service->id;
        $respArray["GetServicesPricesAndAvailabilityResult"]["Warnings"] = (object) array();

        if (empty($serviceOptions) || is_null($serviceOptions)) {
            $respArray["GetServicesPricesAndAvailabilityResult"]["Errors"] = json_decode(json_encode(['Error' => [ 'Description' => 'Service not found']]));
        } else {
            $respArray["GetServicesPricesAndAvailabilityResult"]["Errors"] = (object) array();
            foreach ($serviceOptions as $key => $price) {                       
                $weekDaynights = 0;
                $sell_price = ceil($price->sell_price*$exchangeRate);
                $buy_price = ceil($price->buy_price*$exchangeRate);                    
                $chargingPolicyMultiplicand = $this->multiplicandByChargingPolicy($price, $startDate, $endDate, $rooms[$price->occupancy_id]["QUANTITY"], $rooms[$price->occupancy_id]["NO_OF_PASSENGERS"], $totalNights);
                $multiplicand = $chargingPolicyMultiplicand['multiplicand'];
                
                // Getting total price for a option with respect to Week Days if exists -  Start
                if(!isset($price->price_band_id) || empty($price->price_band_id)) { // we are considering Price band doesn't have week day prices                    
                    $weekDaynights = $totalNights + (preg_match("/day/i",$price->policy_name) ? 1 : ($totalNights == 0 ? 1 : 0));
                    $weekDayPriceArr = $this->getPriceByConsideringWeekDay($service->id,$startDate, $price->season_period_id,$price->option_id, $weekDaynights, $exchangeRate,$chargingPolicyMultiplicand); // getting price for per night per person
                    if(!empty($weekDayPriceArr)) { 
                        $buy_price = $weekDayPriceArr['buy_price'];
                        $sell_price = $weekDayPriceArr['sell_price'];                        
                        $multiplicand = $rooms[$price->occupancy_id]["QUANTITY"]; // if room based.. as we are already calculating price for each day -  this is Quantity based
                        // if the charging policy is based on person -  this is Person based
                        if($price->room_based == 0 ) {
                          $multiplicand *= $rooms[$price->occupancy_id]["NO_OF_PASSENGERS"];  
                        }
                    } 
                }
                // Getting total price for a option with respect to Week Days if exists -  End
                if (!empty($price->policy_id) || !empty($price->price_band_id)) {
                    
                    if (!isset($totalBuyingPrice[$price->option_id])) {
                        $totalBuyingPrice[$price->option_id] = $totalSellingPrice[$price->option_id] = 0;
                    }

                    $mealPlan = ["MealPlanID" => $price->meal_id, 
                                 "MealPlanName" =>$price->meal_name, 
                                 "MealPlanCode" => $price->meal_name.$price->meal_id];
                    
                    
                    $totalBuyingPrice[$price->option_id] = ceil($buy_price*$multiplicand);
                    $totalSellingPrice[$price->option_id] = ceil($sell_price*$multiplicand);

                    $values = array(
                        "MaxChild" => $price->max_children,
                        "MaxAdult" => $price->max_adults,
                        "OptionOccupancy" => array(
                            "Adults" => $price->max_adults, 
                            "Children" => $price->max_children),
                        "Occupancy" => $price->occupancy_id, 
                        "Currency" => $toCurrency,
                        "TotalSellingPrice" => $totalSellingPrice[$price->option_id],
                        "TotalBuyingPrice" => $totalBuyingPrice[$price->option_id],
                        "RatesOptionID" => $price->option_id, 
                        "OptionID" => $price->ts_option_id,  
                        "ServiceOptionName" => $price->option_name
                    );                

                    $respArray["GetServicesPricesAndAvailabilityResult"]["Services"]["PriceAndAvailabilityService"]["ServiceOptions"]["PriceAndAvailabilityResponseServiceOption"][$price->option_id] = $values;
                    $optionPrices[$price->option_id] = [
                        "BuyPrice" => $buy_price, 
                        "SellPrice" => $sell_price, 
                        "MealPlan" => $mealPlan, 
                        "ChargingPolicyName" => $price->policy_name];
            
                    $respArray["GetServicesPricesAndAvailabilityResult"]["Services"]["PriceAndAvailabilityService"]["ServiceOptions"]["PriceAndAvailabilityResponseServiceOption"][$price->option_id]["Prices"]["PriceAndAvailabilityResponsePricing"] = $optionPrices[$price->option_id];
                }
            }
            $priceValues = array_values($respArray["GetServicesPricesAndAvailabilityResult"]["Services"]["PriceAndAvailabilityService"]["ServiceOptions"]["PriceAndAvailabilityResponseServiceOption"]);
            $respArray["GetServicesPricesAndAvailabilityResult"]["Services"]["PriceAndAvailabilityService"]["ServiceOptions"]["PriceAndAvailabilityResponseServiceOption"] = $priceValues;
        }

        return $respArray;
    }

    function calculateServiceExtraRate($service, $startDate, $endDate, $toCurrency, $noOfPeople) {
                
        if(!empty($toCurrency)) {
            $exchangeRate = $this->exchangeRateRepository->exchangeRate($service->currency->code, $toCurrency);
        } else {
            $exchangeRate = 1;
            $toCurrency = $service->currency->code;
        }
        $carbonEnd = Carbon::parse($endDate);
        $totalNights = $carbonEnd->diffInDays(Carbon::parse($startDate));
        $actualEnd = $carbonEnd->subDay()->format('Y-m-d');
        $startDate = Carbon::parse($startDate)->format('Y-m-d');
        $serviceExtras = $this->serviceExtrasAndRates($service->id, $startDate, $actualEnd);        
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
                
                $sell_price = ceil($extra->sell_price*$exchangeRate);
                $buy_price = ceil($extra->buy_price*$exchangeRate);
                $chargingPolicyMultiplicand = $this->multiplicandByChargingPolicy($extra, $startDate, $endDate, 1, 1, $totalNights);
                $multiplicand = $chargingPolicyMultiplicand['multiplicand'];
                $weekDaynights = $totalNights + (preg_match("/day/i",$extra->policy_name) ? 1 : ($totalNights == 0 ? 1 : 0));
                $weekDayPriceArr = $this->getPriceByConsideringWeekDay($service->id,$startDate, $extra->season_period_id,$extra->extra_id, $weekDaynights, $exchangeRate,$chargingPolicyMultiplicand); // getting price for per night per person
                if(!empty($weekDayPriceArr)) {
                    $sell_price = $weekDayPriceArr['sell_price']; 
                    $buy_price = $weekDayPriceArr['buy_price'];
                    $multiplicand = 1; // if room based.. as we are already calculating price for each day
                } 
                
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
                    "TOTALPRICE" => ceil($sell_price*$multiplicand)
                );
               
                $respArray["ServiceExtrasAndPricesResponse"]["ResponseList"]["ServiceExtras"][] = $value;
                $price = array(
                    "PriceId" => $extra->price_id,
                    "PriceDate" => $extra->start,
                    "CurrencyIsoCode" => $toCurrency,
                    "PriceAmount" => $sell_price,
                    "BuyPrice" => $buy_price,
                    "ChargingPolicyName" => $extra->policy_name
                );
                $respArray["ServiceExtrasAndPricesResponse"]["ResponseList"]["ServiceExtras"][$key]["ExtraPrices"]["ServiceExtraPrice"] = $price;
            }
        }

        return $respArray;
    }
}
