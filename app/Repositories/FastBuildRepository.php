<?php

namespace App\Repositories;

use App\Models\Service;
use App\Models\ServiceOption;
use App\Models\Price;
use App\Models\Region;
use App\Models\Supplier;
use App\Models\Meal;
use App\Models\MealOption;
use App\Models\ServiceType;
use App\Models\Currency;
use App\Models\Occupancy;
use App\Models\ServicePolicy;
use App\Models\Contract;
use App\Models\ContractPeriod;
use App\Models\SeasonPeriod;
use App\Models\Season;
use DB;
use Carbon\Carbon;
use DateTime;
use DateInterval;
use DatePeriod;

class FastBuildRepository {
    const POLICYID = 38; // Fast 

    function createCity($params) {
        $tsId = $params["region_tsid"];
        $name = $params["region_name"];
        $parentId = $params["parent_region_id"];

        $parentObj = Region::where('ts_id', $parentId)->first();
        $regionParams = array('ts_id' => $tsId, 'name' => $name, 'parent_id' => ($parentObj ? $parentObj->id : 0));

        try {
            $regionObj = Region::firstOrCreate($regionParams);
            $response = array("Success" => "City has been created Successfully!");
        } catch (\Exception $e) {
            $response = array("Error" => "Caught exception: " . $e->getMessage());
        }

        return $response;
    }

    function createService($params) {

        try {
            $regionTsId = $params["region_tsid"];
            $serviceTsId = $params["service_tsid"];
            $serviceName = $params["service_name"];
            $serviceTypeTsId = $params["service_type"];
            $currency = $params["currency"];
            $supplierName = $params["supplier_name"];
            $mealName = $params["meals"];
            $mealObj = null;
            $serviceTypeObj = ServiceType::where('ts_id', $serviceTypeTsId)->first();
            $currencyObj = Currency::where('code', $currency)->first();
            if ($mealName) {
                $mealObj = Meal::where('name', $mealName)->first();
            }
            $regionObj = Region::where('ts_id', $regionTsId)->first();
            $supplierObj = Supplier::firstOrCreate(array('name' => $supplierName, 'region_id' => $regionObj->id));

            // Find or Create Service
            $serviceParams = array('ts_id' => $serviceTsId,
                'name' => $serviceName,
                'region_id' => $regionObj->id,
                'currency_id' => $currencyObj->id,
                'service_type_id' => $serviceTypeObj->id,
                'supplier_id' => $supplierObj->id
            );
            $serviceObj = Service::firstOrCreate($serviceParams);

            foreach ($params["option"] as $key => $option) {
                $occupancyId = $option["occupancy_id"];
                $occupancyObj = Occupancy::find($occupancyId);
                $optionName = $option["option_name"];

                // Find Or Create Service Option
                $optionObj = null;
                if ($optionName) {
                    $optionParams = array('occupancy_id' => $occupancyObj->id, 'name' => $optionName, 'service_id' => $serviceObj->id);
                    $optionObj = ServiceOption::firstOrCreate($optionParams);

                    // Find or Create Meal Option
                    if ($mealObj) {
                        MealOption::firstOrCreate(['meal_id' => $mealObj->id, 'service_option_id' => $optionObj->id]);
                    }
                    // Find or Create Contracts & Seasons
                    $start = $option["start_date"];
                    $end = $option["end_date"];
                    $contractObj = Contract::firstOrCreate(array('name' => "Fastbuild Contract " . $optionObj->id, 'service_id' => $serviceObj->id));
                    $contractPeriodParams = array('name' => "Fastbuild Contract Period" . $optionObj->id, 'start' => date("Y/m/d", strtotime($start)), 'end' => date("Y/m/d", strtotime($end)), 'contract_id' => $contractObj->id);
                    $contractPeriodObj = ContractPeriod::firstOrCreate($contractPeriodParams);
                    $seasonObj = Season::firstOrCreate(array('name' => "Fastbuild Season " . $optionObj->id, 'contract_period_id' => $contractPeriodObj->id));
                    $seasonPeriodParams = array('start' => date("Y/m/d", strtotime($start)), 'end' => date("Y/m/d", strtotime($end)), 'season_id' => $seasonObj->id);
                    $seasonPeriodObj = SeasonPeriod::firstOrCreate($seasonPeriodParams);

                    // Find or Create Prices
                    $buyPrice = $option["buy_price"];
                    $sellPrice = $option["sell_price"];
                    $priceParams = array('season_period_id' => $seasonPeriodObj->id,
                        'buy_price' => $buyPrice,
                        'sell_price' => $sellPrice,
                        'service_id' => $serviceObj->id,
                        'priceable_id' => $optionObj->id,
                        'priceable_type' => 'App\Models\ServiceOption'
                    );
                    $priceObj = null;
                    if ($optionObj) {
                        $priceObj = Price::firstOrCreate($priceParams);
                    }
                    // Find or Create Service Policies
                    if ($priceObj) {
                        $policyParams = array('charging_policy_id' => self::POLICYID, 'price_id' => $priceObj->id);
                        ServicePolicy::firstOrCreate($policyParams);
                    }
                }
            }

            $response = array('Success' => "Service " . $serviceObj->ts_id . " / " . $serviceObj->name . " has been created...\n");
        } catch (\Exception $e) {
            $response = array("Error" => "Caught exception: " . $e->getMessage());
        }

        return $response;
    }

}
