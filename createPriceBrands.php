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
    
    // $tsId = $row["ID"];
    $tsId = $row["ID"];
    $name = $row["NAME"];
    $min = $row["MIN"];
    $max = $row["MAX"];
    
    print_r($row);

    $pBrandParams = array('ts_id' => $tsId, 'name' => $name, 'min' => $min, 'max' => $max);

    $obj = Models\PriceBrand::firstOrCreate($pBrandParams);
    echo "\033[1m".$obj->name."\033[0m has been added to PriceBrand table\n";
}


?>