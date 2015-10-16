<?php

namespace App\Controllers;

use BaseController;
use Input;
use Validator;
use Response;
use App\Repositories\RatesRepository;
use App\Services\TravelStudioService;
use App\Services\ApiService;

class ApiController extends BaseController
{
    public $serviceRules = [
        'SERVICEIDs' => 'required',
        'SERVICETYPEID' => 'required|exists:service_types,id',
        'START_DATE' => 'required|date',
        'NUMBER_OF_NIGHTS' => 'required|numeric'
    ];

    public $serviceExtraRules = [
        "SERVICEID" => "required|numeric",
        "FROMDATE" => "required|date",
        "TODATE" => "required|date",
        "CURRENCYISOCODE" => "required"
    ];
    
    //SERVICEIDs = 1359,1214,1247,8760,1475,1675,26666
    //seasonperiod=3952,3970,3993,4215,4334,4351
    
    //id = 1,17,24,163,189,405,407
    
    // 1675 => 2015-04-01 - 2015-09-30   =>>    2015-10-01 - 2016-03-31
    // 1675 => 2016-04-01 - 2016-09-30   =>>    2016-10-01 - 2017-03-31
    
    // 1475 => 2016-12-16 - 2017-01-15   =>>   2017-01-16 - 2017-02-28 
   
    
    
    // 26666 => 2015-01-09 - 2015-04-15  ==>    2015-04-16 - 2015-09-30
    
    // 1359 => 11/05/2017 (05 Nov ) -  12 days/11 nights
    // 26666 => 04/08/2015 (8 April ) - 04/19/2015 (19th April) - 12 days/11 nights
    // 
    // 1475 => 01/11/2017 (11 Jan ) - 01/22/2017 (22th April) - 12 days/11 nights
    // 
    // 1675 => 09/25/2015 (25 Sept ) - 10/06/2015 (6th Oct) - 12 days/11 nights
    // 1675 => 09/25/2016 (25 Sept ) - 10/06/2017 (6th Oct) - 12 days/11 nights
    
    
    
    /*
     * 04/08/2015 (8 April ) - 04/19/2015 (19th April) - 12 days/11 nights
     * [season_period] => 2015-04-15 to 2015-09-29 (15 April to 29 Sept)
     * 
     * 
     * 08 april - 1 day/night - checkin
     * 09 april - 2nd night
     * 10 april - 3
     * 11 april - 4
     * 12 april - 5
     * 13 april - 6
     * 14 april - 7
     * 15 april - 8
     * 16 april - 9
     * 17 april - 10
     * 18 april - 11 day/night
     * 19 april - 12 - day checkout
     */ 
    

public $requestData = array ( 'IncomingRequest' => array ( 'ROOMS_REQUIRED' => array ( 'ROOM' => array ( 0 => array ( 'OCCUPANCY' => '3', 'QUANTITY' => 4, 'NO_OF_PASSENGERS' => 2, 'CHILDREN' => array ( 'CHILD_RATE' => array ( 'CHILD_QUANTITY' => '0', 'CHILD_AGE' => '5', ), ), ), 1 => array ( 'OCCUPANCY' => '7', 'QUANTITY' => 1, 'NO_OF_PASSENGERS' => 1, 'CHILDREN' => array ( 'CHILD_RATE' => array ( 'CHILD_QUANTITY' => '1', 'CHILD_AGE' => '5', ), ), ), 2 => array ( 'OCCUPANCY' => '8', 'QUANTITY' => 1, 'NO_OF_PASSENGERS' => 4, 'CHILDREN' => array ( 'CHILD_RATE' => array ( 'CHILD_QUANTITY' => '2', 'CHILD_AGE' => '5', ), ), ), 3 => array ( 'OCCUPANCY' => '6', 'QUANTITY' => 1, 'NO_OF_PASSENGERS' => 15, 'CHILDREN' => array ( 'CHILD_RATE' => array ( 'CHILD_QUANTITY' => '10', 'CHILD_AGE' => '5', ), ), ), 4 => array ( 'OCCUPANCY' => '5', 'QUANTITY' => 1, 'NO_OF_PASSENGERS' => 4, 'CHILDREN' => array ( 'CHILD_RATE' => array ( 'CHILD_QUANTITY' => '0', 'CHILD_AGE' => '5', ), ), ), 5 => array ( 'OCCUPANCY' => '1', 'QUANTITY' => 1, 'NO_OF_PASSENGERS' => 2, 'CHILDREN' => array ( 'CHILD_RATE' => array ( 'CHILD_QUANTITY' => '1', 'CHILD_AGE' => '5', ), ), ), 6 => array ( 'OCCUPANCY' => '4', 'QUANTITY' => 1, 'NO_OF_PASSENGERS' => 3, 'CHILDREN' => array ( 'CHILD_RATE' => array ( 'CHILD_QUANTITY' => '0', 'CHILD_AGE' => '5', ), ), ), 7 => array ( 'OCCUPANCY' => '2', 'QUANTITY' => 1, 'NO_OF_PASSENGERS' => 2, 'CHILDREN' => array ( 'CHILD_RATE' => array ( 'CHILD_QUANTITY' => '0', 'CHILD_AGE' => '5', ), ), ), ), ), 'VERSION_HISTORY' => array ( 'LANGUAGE' => 'en-GB', 'LICENCE_KEY' => 'A6C2FAAA-62D7-4A1B-9AB5-C6BF801E7803', ), 'ISMEALPLANSREQUIRED' => 0, 'IMAGENOTREQUIRED' => 1, 'ReturnMatchCode' => 'true', 'SEARCHWITHFACILITIES_OPTIONS' => 'ALL', 'NotesRequired' => false, 'SERVICEIDs' => '26666', 'START_DATE' => '04/07/2015', 'NUMBER_OF_NIGHTS' => 12, 'AVAILABLE_ONLY' => false, 'GET_START_PRICE' => true, 'CURRENCY' => 'USD', 'SERVICETYPEID' => 2, 'RETURN_ONLY_NON_ACCOM_SERVICES' => false, 'ROOM_REPLY' => array ( 'ANY_ROOM' => 'true', ), 'DoNotReturnNonRefundable' => false, 'DoNotReturnWithCancellationPenalty' => false, 'BESTSELLER' => false, 'CLIENT_ID' => 0, 'BOOKING_TYPE_ID' => 0, 'BOOKINGTYPE' => 0, 'PRICETYPE' => 0, 'SERVICETYPERATINGTYPEID' => 0, 'SERVICETYPERATINGID' => 0, 'IsServiceOptionDescriptionRequired' => 'true', 'IsServiceInfoRequired' => 'true', 'ReturnMandatoryExtraPrices' => false, 'NATIONALITYID' => 0, 'ReturnAttachedOptionExtra' => false, 'SERVICESEARCHTYPE' => 'ENHANCED', 'ReturnAppliedOptionChargingPolicyDetails' => false, ), );

    // public $requestData = array("IncomingRequest" => array(
    //     "VERSION_HISTORY" => array("LANGUAGE" => "en-GB", "LICENCE_KEY" => "A6C2FAAA-62D7-4A1B-9AB5C6BF801E7803"),
    //     "ISMEALPLANSREQUIRED" => 1,
    //     "IMAGENOTREQUIRED" => 1,
    //     "ReturnMatchCode" => "true",
    //     "SEARCHWITHFACILITIES_OPTIONS" => "ALL",
    //     "NotesRequired" => false,
    //     "SERVICEIDs" => "1210",
    //     "START_DATE" => "10/01/2015",
    //     "NUMBER_OF_NIGHTS" => 1,
    //     "AVAILABLE_ONLY" => false,
    //     "GET_START_PRICE" => true,
    //     "CURRENCY" => "USD",
    //     "SERVICETYPEID" => 2,
    //     "RETURN_ONLY_NON_ACCOM_SERVICES" => false,
    //     "ROOM_REPLY" => array("ALL_ROOM" => true),
    //     "DoNotReturnNonRefundable" => false,
    //     "DoNotReturnWithCancellationPenalty" => false,
    //     "ROOMS_REQUIRED" => array("ROOM" => array("OCCUPANCY" => 2,"QUANTITY"=>1,"NO_OF_PASSENGERS"=>2)),
    //     "BESTSELLER" => false,
    //     "CLIENT_ID" => 0,
    //     "BOOKING_TYPE_ID" => 0,
    //     "BOOKINGTYPE" => 0,
    //     "PRICETYPE" => 0,
    //     "SERVICETYPERATINGTYPEID" => 0,
    //     "SERVICETYPERATINGID" => 0,
    //     "IsServiceOptionDescriptionRequired" => "true",
    //     "IsServiceInfoRequired" => "true",
    //     "ReturnMandatoryExtraPrices" => false,
    //     "NATIONALITYID" => 0,
    //     "ReturnAttachedOptionExtra" => false,
    //     "SERVICESEARCHTYPE" => "ENHANCED",
    //     "ReturnAppliedOptionChargingPolicyDetails" => false
    //     )
    // );


    public function __construct(RatesRepository $ratesRepo, TravelStudioService $tsService, ApiService $apiService)
    {
        $this->ratesRepo = $ratesRepo;
        $this->tsService = $tsService;
        $this->apiService = $apiService;
    }

    public function GetServicesPricesAndAvailability()
    {

         $requestData = json_decode(json_encode($this->requestData), true);
        //$requestData = json_decode(Input::get('data'), true);

        $validator = Validator::make($requestData['IncomingRequest'], $this->serviceRules);
        if ($validator->fails()){
            foreach ($validator->errors()->all() as $key => $message) {
                $response["GetServicesPricesAndAvailabilityResult"]["Errors"]["Error"][]["Description"] = $message; 
            }
        }
        else 
        {
            if (isset($requestData['IncomingRequest']["ROOMS_REQUIRED"]["ROOM"]["QUANTITY"])) {
                $quantity = $requestData['IncomingRequest']["ROOMS_REQUIRED"]["ROOM"]["QUANTITY"];
            } else {
                $quantity = $requestData['IncomingRequest']["ROOMS_REQUIRED"]["ROOM"][0]["QUANTITY"];                
            }
            
            $response = $this->apiService->collectServicePrices($requestData['IncomingRequest']['SERVICEIDs'], $requestData['IncomingRequest']['START_DATE'], $requestData['IncomingRequest']['NUMBER_OF_NIGHTS'], $requestData['IncomingRequest']["CURRENCY"], $quantity);
            if( !$this->apiService->isRatesAvailableLocally )
            {
                $funcName = __FUNCTION__;
                $response = $this->tsService->pullRatesFromTravelStudio($funcName, $requestData);
            }

            if (isset($response)){
                return Response::json($response, 200);
            }
        }
    }

    //04/08/2015 (8 April ) - 04/20/2015 (20th April)
    // 26666 => 2015-04-08 (8 April ) - 2015-04-19 (19th April) - 12 days
    // 
    // 1475 => 2017-01-11 (11 Jan ) -  2017-01-22  (22nd Jan) - 12 days
    // 
    // 1675 => 2015-09-25  (25 Sept ) -  2015-10-06  (6th Oct) - 12 days
    // 1675 => 2016-09-25 (25 Sept ) - 2016-10-06 (6th Oct) - 12 days
     public $extraRequest = array("IncomingRequest" => 
         array( "Authenticate" => 
             array("LICENSEKEY" => "A6C2FAAA-62D7-4A1B-9AB5-C6BF801E7803", "PASSENGERID" => "0", "Connector" => "enmTS"),
             "BOOKING_TYPE_ID" => 0 ,
             "PRICE_TYPE_ID" => 0,
             "PriceCode" => 0,
             "SERVICEID" => 1475,
             "FROMDATE" => "2017-01-11",
             "TODATE" => "2017-01-22",
//             "SERVICEID" => 1210,
//             "FROMDATE" => "2015-04-01",
//             "TODATE" => "2015-04-02",
             "ReturnLinkedServiceOptions" => false,
             "IGNORECHILDAGE" => false,
             "RETURNONLYNONACCOMODATIONSERVICES" => true,
             "APPLYEXCHANGERATES" => true,
             "CURRENCYISOCODE" => "EUR" ,
             "ClientId" => 0,
             "ReturnAppliedChargingPolicyDetails" => true,
             "ExtrasRequired" => array("ExtraDetail" => array("OccupancyID" => 1, "Quantity" => 2, "Adults" => 2))
         )
     );

    public function GetServiceExtraPrices()
    {
         $extraRequest = $this->extraRequest;
        //$extraRequest = json_decode(Input::get('data'), true);
        $validator = Validator::make($extraRequest['IncomingRequest'], $this->serviceExtraRules);
        
        if ($validator->fails()){
            foreach ($validator->errors()->all() as $key => $message) {
                $response["ServiceExtrasAndPricesResponse"]["Errors"]["Error"][]["Description"] = $message; 
            }
        }
        else 
        {   
            $response = $this->apiService->collectExtraPrices($extraRequest['IncomingRequest']['SERVICEID'], $extraRequest['IncomingRequest']['FROMDATE'], $extraRequest['IncomingRequest']['TODATE'], $extraRequest["IncomingRequest"]["CURRENCYISOCODE"], $extraRequest['IncomingRequest']["ExtrasRequired"]["ExtraDetail"]["Quantity"]);
            if( !$this->apiService->isRatesAvailableLocally )
            {
                $funcName = __FUNCTION__;
                $response = $this->tsService->pullRatesFromTravelStudio($funcName, $extraRequest);
            }

            if (isset($response)){
                return Response::json($response, 200);
            }
        }
    }

    public function callFunction($funcName)
    {
        if (method_exists($this, $funcName)) {
            return call_user_func([$this,$funcName]);
        } else {
            $params = json_decode(Input::get('data'), true);
            $dataFromTs = $this->tsService->pullRatesFromTravelStudio($funcName, $params);
            if (isset($dataFromTs)){
                return Response::json($dataFromTs);
            }
        }
    }

}
