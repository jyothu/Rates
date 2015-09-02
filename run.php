<?php
include "vendor/autoload.php";
include "config/database.php";

use App\Models as Models;

if( !isset( $argv[1] ) ){
    echo "Expecting CSV File\n";
    echo "Usage :  \033[1mphp run.php <Full Path To CSV File>\033[0m \n";
    exit;
}

$csvFile = new Keboola\Csv\CsvFile( $argv[1] );
foreach($csvFile as $row) {

    $country = $row[0];
	$region = $row[1];
	$serviceId = $row[2];
	$serviceName = $row[3];
	$serviceType = $row[4];
	$supplierName = $row[5];
	$meal = $row[6];
	$option = $row[7];
	$occupancy = $row[8];
	$adult = $row[9];
	$child = $row[10];
	$infant = $row[11];
	$ageOfChild = $row[12];
	$policy = $row[13];
	$season = $row[14];
	$start = $row[15];
	$end = $row[16];
	$currency = $row[17];
	$buyingPrice = $row[18];
	$margin = $row[19];
	$sellingPrice = $row[20];
    
    $serviceTypeObj = Models\ServiceType::firstOrCreate(array('name' => $serviceType));
    $currencyObj = Models\Currency::firstOrCreate(array('code' => $currency));
    $regionObj = Models\Region::firstOrCreate(array('name' => $region));
    $supplierObj = $regionObj->suppliers()->firstOrCreate(array('name' => $supplierName));
    $occupancyObj = Models\Occupancy::firstOrCreate(array('name' => $occupancy));
    $mealObj = Models\Meal::firstOrCreate(array('name' => $meal));
    
    
    // Find or Create Service
    $serviceParams = array('id' => $serviceId,
    	'name' => $serviceName,
    	'region_id' => $regionObj->id,
    	'currency_id' => $currencyObj->id,
    	'service_type_id' => $serviceTypeObj->id,
    	'supplier_id' => $supplierObj->id,
    	'name' => $serviceName
    );

    $serviceObj = Models\Service::firstOrCreate( $serviceParams );

    // Find or Create Season
    $seasonObj = Models\Season::firstOrCreate(array('service_id' => $serviceId , 'name' => "Season ".$season));
    $seasonPeriodParams = array( 'start' =>  date("Y/m/d", strtotime($start)), 'end' => date("Y/m/d", strtotime($end)) );
    $seasonPeriodObj = $seasonObj->season_periods()->firstOrCreate( $seasonPeriodParams );

    // Find Or Create Service Option
    $serviceOptionParams = array('service_id' => $serviceId, 'occupancy_id' => $occupancyObj->id, 'name' => $option." (".$occupancy.")");
    $serviceOptionObj = Models\ServiceOption::firstOrCreate( $serviceOptionParams );


    // Find or Create Meal Option
    $mealParams = array('meal_id' => $mealObj->id, 'service_option_id' => $serviceOptionObj->id);
    $mealOptionObj = Models\MealOption::firstOrCreate( $mealParams );


    // // Find or Create Prices 
    $priceParams = array('season_period_id' => $seasonPeriodObj->id,
        'buy_price' => $buyingPrice,
        'sell_price' => $sellingPrice,
        'service_id' => $serviceId
    );
    $serviceOptionObj->prices()->firstOrCreate( $priceParams );

    echo "Service ".$serviceObj->id." / ".$serviceObj->name." has been created...\n";
}


?>