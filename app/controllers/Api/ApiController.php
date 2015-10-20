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

    // public $requestData = array ( 'IncomingRequest' => array ( 'ROOMS_REQUIRED' => array ( 'ROOM' => array ( 0 => array ( 'OCCUPANCY' => '3', 'QUANTITY' => 1, 'NO_OF_PASSENGERS' => 2, 'CHILDREN' => array ( 'CHILD_RATE' => array ( 'CHILD_QUANTITY' => '0', 'CHILD_AGE' => '5', ), ), ), 1 => array ( 'OCCUPANCY' => '7', 'QUANTITY' => 1, 'NO_OF_PASSENGERS' => 1, 'CHILDREN' => array ( 'CHILD_RATE' => array ( 'CHILD_QUANTITY' => '1', 'CHILD_AGE' => '5', ), ), ), 2 => array ( 'OCCUPANCY' => '8', 'QUANTITY' => 1, 'NO_OF_PASSENGERS' => 4, 'CHILDREN' => array ( 'CHILD_RATE' => array ( 'CHILD_QUANTITY' => '2', 'CHILD_AGE' => '5', ), ), ), 3 => array ( 'OCCUPANCY' => '6', 'QUANTITY' => 1, 'NO_OF_PASSENGERS' => 15, 'CHILDREN' => array ( 'CHILD_RATE' => array ( 'CHILD_QUANTITY' => '10', 'CHILD_AGE' => '5', ), ), ), 4 => array ( 'OCCUPANCY' => '5', 'QUANTITY' => 1, 'NO_OF_PASSENGERS' => 4, 'CHILDREN' => array ( 'CHILD_RATE' => array ( 'CHILD_QUANTITY' => '0', 'CHILD_AGE' => '5', ), ), ), 5 => array ( 'OCCUPANCY' => '1', 'QUANTITY' => 1, 'NO_OF_PASSENGERS' => 2, 'CHILDREN' => array ( 'CHILD_RATE' => array ( 'CHILD_QUANTITY' => '1', 'CHILD_AGE' => '5', ), ), ), 6 => array ( 'OCCUPANCY' => '4', 'QUANTITY' => 1, 'NO_OF_PASSENGERS' => 3, 'CHILDREN' => array ( 'CHILD_RATE' => array ( 'CHILD_QUANTITY' => '0', 'CHILD_AGE' => '5', ), ), ), 7 => array ( 'OCCUPANCY' => '2', 'QUANTITY' => 1, 'NO_OF_PASSENGERS' => 2, 'CHILDREN' => array ( 'CHILD_RATE' => array ( 'CHILD_QUANTITY' => '0', 'CHILD_AGE' => '5', ), ), ), ), ), 'VERSION_HISTORY' => array ( 'LANGUAGE' => 'en-GB', 'LICENCE_KEY' => 'A6C2FAAA-62D7-4A1B-9AB5-C6BF801E7803', ), 'ISMEALPLANSREQUIRED' => 0, 'IMAGENOTREQUIRED' => 1, 'ReturnMatchCode' => 'true', 'SEARCHWITHFACILITIES_OPTIONS' => 'ALL', 'NotesRequired' => false, 'SERVICEIDs' => '1210', 'START_DATE' => '10/11/2015', 'NUMBER_OF_NIGHTS' => 1, 'AVAILABLE_ONLY' => false, 'GET_START_PRICE' => true, 'CURRENCY' => 'USD', 'SERVICETYPEID' => 2, 'RETURN_ONLY_NON_ACCOM_SERVICES' => false, 'ROOM_REPLY' => array ( 'ANY_ROOM' => 'true', ), 'DoNotReturnNonRefundable' => false, 'DoNotReturnWithCancellationPenalty' => false, 'BESTSELLER' => false, 'CLIENT_ID' => 0, 'BOOKING_TYPE_ID' => 0, 'BOOKINGTYPE' => 0, 'PRICETYPE' => 0, 'SERVICETYPERATINGTYPEID' => 0, 'SERVICETYPERATINGID' => 0, 'IsServiceOptionDescriptionRequired' => 'true', 'IsServiceInfoRequired' => 'true', 'ReturnMandatoryExtraPrices' => false, 'NATIONALITYID' => 0, 'ReturnAttachedOptionExtra' => false, 'SERVICESEARCHTYPE' => 'ENHANCED', 'ReturnAppliedOptionChargingPolicyDetails' => false, ), );

    public function __construct(RatesRepository $ratesRepo, TravelStudioService $tsService, ApiService $apiService)
    {
        $this->ratesRepo = $ratesRepo;
        $this->tsService = $tsService;
        $this->apiService = $apiService;
    }

    public function GetServicesPricesAndAvailability()
    {

         // $requestData = json_decode(json_encode($this->requestData), true);
        $requestData = json_decode(Input::get('data'), true);

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
                $noOfPeople = $requestData['IncomingRequest']["ROOMS_REQUIRED"]["ROOM"]["NO_OF_PASSENGERS"];
            } else {
                $quantity = $requestData['IncomingRequest']["ROOMS_REQUIRED"]["ROOM"][0]["QUANTITY"];
                $noOfPeople = $requestData['IncomingRequest']["ROOMS_REQUIRED"]["ROOM"][0]["NO_OF_PASSENGERS"]; 
            }
            
            $response = $this->apiService->collectServicePrices($requestData['IncomingRequest']['SERVICEIDs'], $requestData['IncomingRequest']['START_DATE'], $requestData['IncomingRequest']['NUMBER_OF_NIGHTS'], $requestData['IncomingRequest']["CURRENCY"], $quantity, $noOfPeople);
            
            if( !$this->apiService->isRatesAvailableLocally )
            {
                // $funcName = __FUNCTION__;
                // $response = $this->tsService->pullRatesFromTravelStudio($funcName, $requestData);
                $response["GetServicesPricesAndAvailabilityResult"]["Errors"] = json_decode(json_encode(['Error' => [ 'Description' => 'Service not found']]));
            }

            if (isset($response)){
                return Response::json($response, 200);
            }
        }
    }

     // public $extraRequest = array("IncomingRequest" => 
     //     array( "Authenticate" => 
     //        array("LICENSEKEY" => "A6C2FAAA-62D7-4A1B-9AB5-C6BF801E7803", "PASSENGERID" => "0", "Connector" => "enmTS"),
     //        "BOOKING_TYPE_ID" => 0 ,
     //        "PRICE_TYPE_ID" => 0,
     //        "PriceCode" => 0,
     //        "SERVICEID" => 1210,
     //        "FROMDATE" => "2015-04-01",
     //        "TODATE" => "2015-04-02",
     //        "ReturnLinkedServiceOptions" => false,
     //        "IGNORECHILDAGE" => false,
     //        "RETURNONLYNONACCOMODATIONSERVICES" => true,
     //        "APPLYEXCHANGERATES" => true,
     //        "CURRENCYISOCODE" => "EUR" ,
     //        "ClientId" => 0,
     //        "ReturnAppliedChargingPolicyDetails" => true,
     //        "ExtrasRequired" => array("ExtraDetail" => array("OccupancyID" => 1, "Quantity" => 10, "Adults" => 2))
     //     )
     // );

    public function GetServiceExtraPrices()
    {
        // $extraRequest = $this->extraRequest;
        $extraRequest = json_decode(Input::get('data'), true);
        $validator = Validator::make($extraRequest['IncomingRequest'], $this->serviceExtraRules);
        
        if ($validator->fails()){
            foreach ($validator->errors()->all() as $key => $message) {
                $response["ServiceExtrasAndPricesResponse"]["Errors"]["Error"][]["Description"] = $message; 
            }
        }
        else 
        {   
            $response = $this->apiService->collectExtraPrices($extraRequest['IncomingRequest']['SERVICEID'], $extraRequest['IncomingRequest']['FROMDATE'], $extraRequest['IncomingRequest']['TODATE'], $extraRequest["IncomingRequest"]["CURRENCYISOCODE"], $extraRequest['IncomingRequest']["ExtrasRequired"]["ExtraDetail"]["Adults"]);
            if( !$this->apiService->isRatesAvailableLocally )
            {
                // $funcName = __FUNCTION__;
                // $response = $this->tsService->pullRatesFromTravelStudio($funcName, $extraRequest);
            
                $responseValue = array(
                    "Errors" => (object) array(),
                    "ServiceId" => 0,
                    "ServiceCode" => 0,
                    "ServiceName" => 0,
                    "ServiceTypeId" => 0,
                    "ResponseList" => (object) array()
                );
                $response["ServiceExtrasAndPricesResponse"] = $responseValue;
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
