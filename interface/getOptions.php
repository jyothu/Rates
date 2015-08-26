<?php
include "../vendor/autoload.php";
include "../config/database.php";

use Enchanting\MigrateTs\Models as Models;

$service = Models\Service::find($_POST['service']);

$serviceOptions = isset( $service ) ? $service->service_options : [];

if( empty( $serviceOptions ) ){
  
    echo "Invalid Service ID";
    // echo "No Service Option found for the Service ID entered. Please try with another."

} else {

	echo '<option>Select Service Options</option>';
	foreach($serviceOptions as $serviceOption)
	    echo '<option value="'.$serviceOption->id.'">'.$serviceOption->name.'</option>';
}

?>