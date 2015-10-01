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
    
	$optionId = $row["Option ID"];
	$extraId = $row["Extra ID"];
    
    print_r($row);

    $optionObj = Models\ServiceOption::where('ts_id', $optionId)->first();
    $extraObj = Models\ServiceExtra::where('ts_id', $extraId)->first();

    if ($optionObj && $extraObj) {
        echo "Linking \033[1m".$optionObj->name."\033[0m & \033[1m".$extraObj->name."\033[0m";
        // $optionObj->service_extra_id = $extraObj->id;
        // $optionObj->save();    
    }

}


?>