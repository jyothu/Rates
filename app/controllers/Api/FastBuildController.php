<?php

namespace App\Controllers;

use BaseController;
use Input;
use Validator;
use Response;
use App\Repositories\FastBuildRepository;

class FastBuildController extends BaseController
{

	// public $fastBuildRules = [
 //        'SERVICEIDs' => 'required',
 //        'SERVICETYPEID' => 'required|exists:service_types,id',
 //        'START_DATE' => 'required|date',
 //        'NUMBER_OF_NIGHTS' => 'required|numeric'
 //    ];

	public function __construct(FastBuildRepository $fastBuildRepo)
    {
        $this->fastBuildRepo = $fastBuildRepo;
    }
  
    public $requestData = array(
    	"fast_build_type" => "service",
        "service_tsid" => 100000,
        "service_name" => "Fast Build Testing",
        "supplier_name" => "Enchanting Travels",
        "service_type" => 2,
        "meals" => "Breakfast",
        "currency" => "USD",
        "region_tsid" => 100000,
        "option" => array(
        	array(
	            "option_name" => "Service Option Fast Build 1",
	            "occupancy_id" => 1,
	            "start_date" => "2015-10-01",
	            "end_date" => "2016-04-01",
	            "buy_price" => 9000,
	            "sell_price" => 10000
            ),
            array(
	            "option_name" => "Service Option Fast Build 2",
	            "occupancy_id" => 2,
	            "start_date" => "2015-10-01",
	            "end_date" => "2016-04-01",
	            "buy_price" => 11000,
	            "sell_price" => 12000
            )
        ),
        // "region_tsid" => 100000,
        // "region_name" => "Murugeshpalay",
        "parent_region_id" => 11016
    );

    public function createServiceOrCity()
    {
        // $requestData = json_decode(Input::get('data'), true);
        // $validator = Validator::make($requestData['IncomingRequest'], $this->serviceRules);
        $requestData = $this->requestData;

        if ($requestData["fast_build_type"] == "service") {
        	$response = $this->fastBuildRepo->createService($requestData);
        } else {
        	$response = $this->fastBuildRepo->createCity($requestData); 
        }

        if (isset($response)){
            return Response::json($response, 200);
        }
    }
    

    public function callFunction($funcName)
    {
        if (method_exists($this, $funcName)) {
            return call_user_func([$this,$funcName]);
        } else {

            return Response::json(array('Error' => "Please check the API Url"));
        }
    }


}