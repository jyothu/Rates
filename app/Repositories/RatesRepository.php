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
        return DB::select("select buy_price, sell_price, monday, tuesday, wednesday, thursday, friday, saturday, sunday, season_period_id, start, end, priceable_id as option_id, service_options.name as option_name, price_bands.id as price_band_id, price_bands.min as price_band_min, price_bands.max as price_band_max, charging_policies.id as policy_id, charging_policies.ts_id as policy_tsid, charging_policies.name as policy_name, charging_policies.room_based as room_based, charging_policies.day_duration as day_duration, occ.name as occupancy_name, occ.id as occupancy_id, max_adults, max_children, meals.id as meal_id, meals.name as meal_name from prices join service_options on (prices.priceable_id = service_options.id) join season_periods on (prices.season_period_id=season_periods.id) join occupancies occ on (service_options.occupancy_id=occ.id) join service_policies on (service_policies.price_id = prices.id) join charging_policies on (service_policies.charging_policy_id = charging_policies.id) left join ( meal_options join meals on (meal_options.meal_id = meals.id) ) on (meal_options.service_option_id = service_options.id) right join ( policy_price_bands join price_bands on (policy_price_bands.price_band_id = price_bands.id AND price_bands.min<=? AND price_bands.max>=?) ) on (policy_price_bands.service_policy_id = service_policies.id) left join week_prices on (prices.id = week_prices.price_id) where priceable_id IN (select id from service_options where service_id=?) AND priceable_type LIKE '%ServiceOption' AND prices.season_period_id IN (select id from season_periods where  start<=? AND end>=? OR start<=? AND end>=?) and occ.id IN ($occupancyIds) and service_options.status=?", [$noOfPeople, $noOfPeople, $serviceId, $startDate, $startDate, $endDate, $endDate, 1]);  
    }

    public function serviceExtrasAndRates($serviceId, $startDate, $endDate)
    {
        return DB::select("select buy_price, sell_price, service_extras.name as extra_name, season_period_id, start, end, charging_policies.id as policy_id, charging_policies.ts_id as policy_tsid, charging_policies.name as policy_name, charging_policies.room_based as room_based, charging_policies.day_duration as day_duration, priceable_id as extra_id, prices.id as price_id, service_extras.ts_id as extra_tsid from prices join service_extras on (prices.priceable_id = service_extras.id AND priceable_type LIKE '%ServiceExtra') join season_periods on (prices.season_period_id=season_periods.id) join service_policies on (service_policies.price_id = prices.id) join charging_policies on (service_policies.charging_policy_id = charging_policies.id) WHERE prices.service_id=? AND season_period_id IN (select id from season_periods where  start<=? AND end>=? OR start<=? AND end>=?) and service_extras.status=?", [$serviceId, $startDate, $startDate, $endDate, $endDate, 1]);
    }

    public function getServiceWithCurrency($serviceId) {
        return Service::with('currency')->find($serviceId);
    }

    // Calculating multiplicand with respect to either charging policy or Price bands.
    public function multiplicandByChargingPolicy($policyObj, $startDate, $endDate, $quantity, $noOfPeople, $totalNights) {
        $multiplicand = 1;
        if ($policyObj->price_band_id) {
            $multiplicand *= $noOfPeople;
        } else if ($policyObj->policy_id) {
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
                    }
                } else { // person based 
                    if ($dayDuration <= '1') {  // per person per day/night
                        $multiplicand *= $noOfPeople*$nights;
                    } else {  // per person per  N day/night     
                        $nnights = ceil($nights / $dayDuration);
                        $multiplicand *= $noOfPeople*$nnights;
                    }
                }
            }
        }
        return $multiplicand;
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

    public function calculateTotalServiceRate($service, $startDate, $toCurrency, $rooms, $totalNights) {
        $exchangeRate = $this->exchangeRateRepository->exchangeRate($service->currency->code, $toCurrency);
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
                if (!empty($price->policy_id) || !empty($price->price_band_id)) {

                    if (!isset($totalBuyingPrice[$price->option_id])) {
                        $totalBuyingPrice[$price->option_id] = $totalSellingPrice[$price->option_id] = 0;
                    }

                    $mealPlan = ["MealPlanID" => $price->meal_id, 
                                 "MealPlanName" =>$price->meal_name, 
                                 "MealPlanCode" => $price->meal_name.$price->meal_id];
                    $multiplicand = $this->multiplicandByChargingPolicy($price, $startDate, $endDate, $rooms[$price->occupancy_id]["QUANTITY"], $rooms[$price->occupancy_id]["NO_OF_PASSENGERS"], $totalNights);
                    $totalBuyingPrice[$price->option_id] = ($price->buy_price)*$multiplicand;
                    $totalSellingPrice[$price->option_id] = ($price->sell_price)*$multiplicand;

                    $values = array(
                        "MaxChild" => $price->max_children,
                        "MaxAdult" => $price->max_adults,
                        "OptionOccupancy" => array(
                            "Adults" => $price->max_adults, 
                            "Children" => $price->max_children),
                        "Occupancy" => $price->occupancy_id, 
                        "Currency" => $toCurrency,
                        "TotalSellingPrice" => ceil(($totalSellingPrice[$price->option_id])*$exchangeRate),
                        "TotalBuyingPrice" => ceil(($totalBuyingPrice[$price->option_id])*$exchangeRate),
                        "OptionID" => $price->option_id, 
                        "ServiceOptionName" => $price->option_name
                    );                

                    $respArray["GetServicesPricesAndAvailabilityResult"]["Services"]["PriceAndAvailabilityService"]["ServiceOptions"]["PriceAndAvailabilityResponseServiceOption"][$price->option_id] = $values;
                    $optionPrices[$price->option_id] = ["BuyPrice" => ($price->buy_price*$exchangeRate), "SellPrice" => ($price->sell_price*$exchangeRate), "MealPlan" => $mealPlan, "ChargingPolicyName" => $price->policy_name];
            
                    $respArray["GetServicesPricesAndAvailabilityResult"]["Services"]["PriceAndAvailabilityService"]["ServiceOptions"]["PriceAndAvailabilityResponseServiceOption"][$price->option_id]["Prices"]["PriceAndAvailabilityResponsePricing"] = $optionPrices[$price->option_id];
                }
            }
            $priceValues = array_values($respArray["GetServicesPricesAndAvailabilityResult"]["Services"]["PriceAndAvailabilityService"]["ServiceOptions"]["PriceAndAvailabilityResponseServiceOption"]);
            $respArray["GetServicesPricesAndAvailabilityResult"]["Services"]["PriceAndAvailabilityService"]["ServiceOptions"]["PriceAndAvailabilityResponseServiceOption"] = $priceValues;
        }

        return $respArray;
    }

    function calculateServiceExtraRate($service, $startDate, $endDate, $toCurrency, $noOfPeople) {

        $exchangeRate = $this->exchangeRateRepository->exchangeRate($service->currency->code, $toCurrency);
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
                $multiplicand = $this->multiplicandByChargingPolicy($extra, $startDate, $endDate, 1, 1, $totalNights);
                
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
                    "TOTALPRICE" => ceil($extra->sell_price*$exchangeRate*$multiplicand)
                );
               
                $respArray["ServiceExtrasAndPricesResponse"]["ResponseList"]["ServiceExtras"][] = $value;
                $price = array(
                    "PriceId" => $extra->price_id,
                    "PriceDate" => $extra->start,
                    "CurrencyIsoCode" => $toCurrency,
                    "PriceAmount" => $extra->sell_price*$exchangeRate,
                    "BuyPrice" => $extra->buy_price*$exchangeRate,
                    "ChargingPolicyName" => $extra->policy_name
                );
                $respArray["ServiceExtrasAndPricesResponse"]["ResponseList"]["ServiceExtras"][$key]["ExtraPrices"]["ServiceExtraPrice"] = $price;
            }
        }

        return $respArray;
    }

}
