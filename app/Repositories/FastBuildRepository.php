<?php

namespace App\Repositories;

use App\Models\Service;
use App\Models\ServiceOption;
use App\Models\Price;
use App\Models\Region;
use App\Models\Meal;
use App\Models\MealOption;
use App\Models\ServiceType;
use App\Models\Currency;
use App\Models\Occupancy;
use App\Models\ServicePolicy;
use DB;
use Carbon\Carbon;
use DateTime;
use DateInterval;
use DatePeriod;

class FastBuildRepository
{
	
	function createCity($params)
	{
        $tsId = $params["region_id"];
        $name = $params["region_name"];
        $parentId = $params["parent_region_id"];

        $parentObj = Region::find($parentId);
        $regionParams = array('ts_id' => $tsId, 'name' => $name, 'parent_id' => ($parentObj ? $parentObj->id : 0));
        
        try {
	        $regionObj = Region::firstorCreate($regionParams);
	        $response = array("Success" => "City has been created Successfully!");
	    } catch (Exception $e) {
	    	$response = array("Error" => "Caught exception: ".$e->getMessage());
	    }
        
        return $response;

	}


	function createService($params)
	{

		$regionTsId = $params["region_tsid"];
		$serviceTsId = $params["service_tsid"];
		$serviceName = $params["service_name"];
		$serviceTypeTsId = $params["service_type"];
		$currency = $params["currency"];
		$supplierName = $params["supplier_name"];
		$mealName = $params["meals"];
		$policyId = 38; // Fast Build
		$serviceTypeObj = ServiceType::where('ts_id', $serviceTypeTsId)->first();
	    $currencyObj = Currency::where('code', $currency)->first();
	    $mealObj = Meal::where('name', $mealName)->first();
	    $regionObj = Region::where('ts_id', $regionTsId)->first();
	    $supplierObj = $regionObj->suppliers()->firstOrCreate(array('name' => $supplierName));
        dd($supplierObj->toArray());
	    // Find or Create Service
	    $serviceParams = array('ts_id' => $serviceTsId,
	    	'name' => $serviceName,
	    	'region_id' => $regionObj->id,
	    	'currency_id' => $currencyObj->id,
	    	'service_type_id' => $serviceTypeObj->id,
	    	'supplier_id' => $supplierObj->id
	    );
	    dd($serviceParams);
	    $serviceObj = Service::firstOrCreate($serviceParams);

	    foreach ($params["option"] as $key => $option) {
			$occupancyId = $option["occupancy_id"];
	    	$occupancyObj = Occupancy::find($occupancyId);
	    	$optionName = $option["option_name"];

		    // Find Or Create Service Option
		    $optionObj = null;
		    if ($optionName) {
				$optionParams = array('occupancy_id' => $occupancyObj->id, 'name' => $optionName);
			    $optionObj = $serviceObj->serviceOptions()->firstOrCreate($optionParams);
		        
		        // Find or Create Meal Option
			    $optionObj->mealOptions()->firstOrCreate(['meal_id' => $mealObj->id]);
                
			    // Find or Create Contracts & Seasons
			    $start = $option["start_date"];
				$end = $option["end_date"];
			    $contractObj = $serviceObj->contracts()->firstOrCreate(array('name' => "Fastbuild Contract ".$optionObj->id));
			    $contractPeriodParams = array('name' => "Fastbuild Contract Period".$optionObj->id, 'start' =>  date("Y/m/d", strtotime($start)), 'end' => date("Y/m/d", strtotime($end)));
			    $contractPeriodObj = $contractObj->contractPeriods()->firstOrCreate($contractPeriodParams);
			    $seasonObj = $contractPeriodObj->seasons()->firstOrCreate(array('name' => "Fastbuild Season ".$optionObj->id));
			    $seasonPeriodParams = array('start' =>  date("Y/m/d", strtotime($start)), 'end' => date("Y/m/d", strtotime($end)));
			    $seasonPeriodObj = $seasonObj->seasonPeriods()->firstOrCreate($seasonPeriodParams);

			    // Find or Create Prices
    			$buyPrice = $option["buy_price"];
			    $sellPrice = $option["sell_price"];
			    $priceParams = array('season_period_id' => $seasonPeriodObj->id,
			        'buy_price' => $buyPrice,
			        'sell_price' => $sellPrice,
			        'service_id' => $serviceObj->id
			       );
			    $priceObj = null;
			    if ($optionObj) {
			    	$priceObj = $optionObj->prices()->firstOrCreate($priceParams);
			    }
			    // Find or Create Service Policies
			    if ($priceObj) {
			        $policyParams = array('charging_policy_id' => $policyId);
			        $priceObj->servicePolicy()->firstOrCreate($policyParams);
			    }
		    }


	    }

    	echo "Service ".$serviceObj->id." / ".$serviceObj->name." has been created...\n";

    }

}