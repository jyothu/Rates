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
    
    $tsId = $row["ID"];
    $name = $row["POLICY NAME"];
    $chargingDuration = $row["CHARGING DURATION"];
    $roomBased = ($row["ROOM BASED"] == "Yes" ? 1 : 0);
    $dayDuration = $row["DURATION DAY"];
    $dayOverlap = ($row["DAY OVERLAP"] == "Yes" ? 1 : 0);
    $capacity = $row["CAPACITY"];
    
    print_r($row);

    $policyParams = array('ts_id' => $tsId,
        'name' => $name, 'charging_duration' => $chargingDuration,
        'day_duration' => $dayDuration, 'room_based' => $roomBased,
        'day_overlap' => $dayOverlap, 'capacity' => $capacity
    );

    $obj = Models\ChargingPolicy::firstOrCreate($policyParams);
    echo "\033[1m".$obj->name."\033[0m has been added to Policy table\n";
}


?>