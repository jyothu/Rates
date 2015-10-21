<?php
include "vendor/autoload.php";
include "config/database.php";

use App\Models as Models;

if( !isset( $argv[1] ) ){
    echo "Expecting CSV File\n";
    echo "Usage :  \033[1mphp run.php <Full Path To CSV File>\033[0m \n";
    exit;
}


$rows = array_map('str_getcsv', file( $argv[1] ));
$header = array_shift($rows);
$csv = array();
foreach ($rows as $row) {
    $csv[] = array_combine($header, $row);
}

foreach($csv as $row) {
    
	$region = $row["REGIONNAME"];
	$serviceId = $row["SERVICEID"];
	$serviceName = $row["SERVICELONGNAME"];
	$serviceType = $row["SERVICETYPENAME"];
	$supplierId = $row["SUPPLIERID"];
	$supplierName = $row["SUPPLIERNAME"];
	$mealName = $row["MEALPLANNAME"];
	$optionId = $row["OPTIONID"];
	$optionName = $row["OPTIONNAME"];
	$extraId = $row["EXTRAID"];
	$extraName = $row["EXTRANAME"];
	$occupancyId = $row["OCCUPANCYTYPEID"];
	$occupancyName = $row["OCCUPANCYTYPENAME"];
	$policyId = $row["CHARGINGPOLICYID"];
	$policyName = $row["CHARGINGPOLICYNAME"];
	$seasonId = $row["SEASONTYPEID"];
	$seasonName = $row["SEASONTYPENAME"];
	$seasonStart = $row["SEASONSTARTDATE"];
	$seasonEnd = $row["SEASONENDDATE"];
	$contractId = $row["ORGANISATIONSUPPLIERCONTRACTID"];
	$contractName = $row["ORGANISATIONSUPPLIERCONTRACTNAME"];
	$contractPeriodId = $row["CONTRACTDURATIONID"];
	$contractPeriodName = $row["CONTRACTDURATIONNAME"];
	$contractStart = $row["CONTRACTDURATIONSTARTDATE"];
	$contractEnd = $row["CONTRACTDURATIONENDDATE"];
	$currency = $row["CURRENCYISOCODE"];
	$buyPrice = $row["BUYPRICE"];
	$margin = $row["MARGIN"];
    $sellPrice = $row["SELLING"];
    $minPriceBand = $row["MIN"];
    $maxPriceBand = $row["MAX"];
	$optionStatus = (($row["Option-status"] == "Unavailable") ? 0 : 1);
    
    print_r($row);

    $serviceTypeObj = Models\ServiceType::firstOrCreate(array('name' => $serviceType));
    $currencyObj = Models\Currency::firstOrCreate(array('code' => $currency));
    $regionObj = Models\Region::firstOrCreate(array('name' => $region));
    $supplierObj = $regionObj->suppliers()->firstOrCreate(array('name' => $supplierName, 'ts_id' => $supplierId));
    if ($occupancyId) {
	    $occupancyObj = Models\Occupancy::firstOrCreate(array('id' => $occupancyId, 'name' => $occupancyName));
    }

    $mealObj = null;
    if ($mealName) {
	    $mealObj = Models\Meal::firstOrCreate(array('name' => $mealName));	
    }
    
    // Find or Create Service
    $serviceParams = array('ts_id' => $serviceId,
    	'name' => trim($serviceName),
    	'region_id' => $regionObj->id,
    	'currency_id' => $currencyObj->id,
    	'service_type_id' => $serviceTypeObj->id,
    	'supplier_id' => $supplierObj->id
    );
    $serviceObj = Models\Service::firstOrCreate( $serviceParams );
    
    // Find or Create Policies
    $policyObj = null;
    if ($policyId) {
        $policyParams = array('ts_id' => $policyId, 'name' => $policyName);
        $policyObj = Models\ChargingPolicy::where('ts_id', $policyId)->where('name', $policyName)->first();
        if (!$policyObj) {
            $roomBased = 0;
            $dayDuration = 1;
            if (preg_match("/room|unit/i", $policyName)) {
                $roomBased = 1;
            }

            if( preg_match('!\d+!', $policyName, $matches)){
                $dayDuration = $matches[0];
            }
            $policyArgs = array('room_based' => $roomBased, 'day_duration' => $dayDuration);
            $policyObj = Models\ChargingPolicy::create( array_merge($policyParams, $policyArgs));
        }
    }
    
    // Find or Create Price Bands
    $priceBandObj = null;
    if ($minPriceBand) {
        $priceBandParams = array('min' => $minPriceBand, 'max' => $maxPriceBand);
        $priceBandObj = Models\PriceBand::firstOrCreate( $priceBandParams );
    }

    // Find or Create Contracts
    $contractObj = $serviceObj->contracts()->firstOrCreate(array('ts_id' => $contractId, 'name' => $contractName));
    $contractPeriodParams = array( 'ts_id' => $contractPeriodId, 'name' => $contractPeriodName, 'start' =>  date("Y/m/d", strtotime($contractStart)), 'end' => date("Y/m/d", strtotime($contractEnd)) );
    $contractPeriodObj = $contractObj->contractPeriods()->firstOrCreate( $contractPeriodParams );

    // Find or Create Season
    $seasonObj = $contractPeriodObj->seasons()->firstOrCreate(array('ts_id' => $seasonId, 'name' => $seasonName));
    $seasonPeriodParams = array( 'start' =>  date("Y/m/d", strtotime($seasonStart)), 'end' => date("Y/m/d", strtotime($seasonEnd)) );
    $seasonPeriodObj = $seasonObj->seasonPeriods()->firstOrCreate( $seasonPeriodParams );

    // Find or Create Service Extras
    $extraObj = null;
    if ($extraId) {
	    $extraParams = array('name' => $extraName, 'ts_id' => $extraId);
	    $extraObj = $serviceObj->serviceExtras()->firstOrCreate( $extraParams );
    }

    // Find Or Create Service Option
    $optionObj = null;
    if ($optionId) {
		$serviceOptionParams = array('occupancy_id' => $occupancyObj->id,
            'name' => $optionName,
            'ts_id' => $optionId,
            'status' => $optionStatus
            );
	    $optionObj = $serviceObj->serviceOptions()->firstOrCreate( $serviceOptionParams );
        
        // Find or Create Meal Option
        if ($mealObj) {
    	    $optionObj->mealOptions()->firstOrCreate( ['meal_id' => $mealObj->id] );
        }
    }

    // Find or Create Prices 
    $priceParams = array('season_period_id' => $seasonPeriodObj->id,
        'buy_price' => $buyPrice,
        'sell_price' => $sellPrice,
        'service_id' => $serviceObj->id
        );

    $priceObj = null;
    if ($extraObj) {
    	$priceObj = $extraObj->prices()->firstOrCreate( $priceParams );
    } elseif ($optionObj) {
    	$priceObj = $optionObj->prices()->firstOrCreate( $priceParams );
    }

    // Find or Create Service Policies
    if ($policyObj) {
        $servicePolicyParams = array('charging_policy_id' => $policyObj->id);
        $priceObj->servicePolicy()->firstOrCreate( $servicePolicyParams );
    }

    // Find or Create Service Price Bands
    if ($priceBandObj) {
        $serviceBandParams = array('price_band_id' => $priceBandObj->id);
        $priceObj->servicePriceBand()->firstOrCreate( $serviceBandParams );
    }

    echo "Service ".$serviceObj->id." / ".$serviceObj->name." has been created...\n";
}


?>