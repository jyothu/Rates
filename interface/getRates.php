<?php
include "../vendor/autoload.php";
include "../config/database.php";

use Enchanting\MigrateTs\Models as Models;
use Carbon\Carbon;

$optionId = $_POST["option"];
$start = $_POST["checkin"];
$end = $_POST["checkout"];

$carbonEnd = Carbon::parse( $end );

$actualEnd = $carbonEnd->subDay()->format('Y-m-d');

$startObj = new DateTime( $start );
$endObj = new DateTime( $end );
$interval = DateInterval::createFromDateString('1 day');
$period = new DatePeriod($startObj, $interval, $endObj);

$prices = DB::select("select buy_price,sell_price,season_period_id,start,end from prices join season_periods on (prices.season_period_id=season_periods.id) where priceable_id=? AND season_period_id IN (select id from season_periods where start<=? AND end>=? OR start<=? AND end>=?)", [$optionId, $start, $start, $actualEnd, $actualEnd]);

// $prices = Models\Price::join('season_periods', 'prices.season_period_id', 'season_periods.id')
// 			->where('priceable_id', "=", 1)
// 			->whereIn('season_period_id', function($query) {
// 				$query->raw("select id from `season_periods` where (start<=? AND end>=? OR start<=? AND end>=?)");
// 			})
// 			->get(['buy_price', 'sell_price', 'season_period_id', 'start', 'end']);


if( count( $prices ) == 1 ) {

    $nights = Carbon::parse($end)->diffInDays(Carbon::parse($start));
	$totalBuyingPrice = ($prices[0]->buy_price)*($nights);
	$totalSellingPrice = ($prices[0]->sell_price)*($nights);

} else {
    
    $totalBuyingPrice = $totalSellingPrice = 0;

    foreach ($prices as $price) {
        
        $carbonStartDate = Carbon::parse($price->start);
        $carbonEndDate = Carbon::parse($price->end);

    	foreach ( $period as $date ){

    		if( Carbon::parse($date->format('Y-m-d'))->between( $carbonStartDate, $carbonEndDate  ) ){

    			$totalBuyingPrice += $price->buy_price;
    	        $totalSellingPrice += $price->sell_price;
    		
    		}
        }
    }

}

echo "<table class='table table-striped'><th>Season Period</th><th>Buying Price</th>
<th>Selling Price</th><th>Total Buying Price</th><th>Total Selling Price</th>";

foreach ($prices as $key=>$price) {

    echo "<tr><td><span class='display-block'>START :".$price->start."</span>
    <span class='display-block'>END :".$price->end."</span></td>
    <td>".$price->buy_price."</td><td>".$price->sell_price."</td>";
    
    if($key == 0){
        echo "<td rowspan=".count( $prices ).">$totalBuyingPrice</td>
        <td rowspan=".count( $prices ).">$totalSellingPrice</td>";
    }

   echo "</tr>";
}
echo "</table>";
?>