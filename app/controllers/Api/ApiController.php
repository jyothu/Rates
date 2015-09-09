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

    // public $requestData = array ( 'IncomingRequest' => array ( 'ROOMS_REQUIRED' => array ( 'ROOM' => array ( 0 => array ( 'OCCUPANCY' => '3', 'QUANTITY' => 1, 'NO_OF_PASSENGERS' => 2, 'CHILDREN' => array ( 'CHILD_RATE' => array ( 'CHILD_QUANTITY' => '0', 'CHILD_AGE' => '5', ), ), ), 1 => array ( 'OCCUPANCY' => '7', 'QUANTITY' => 1, 'NO_OF_PASSENGERS' => 3, 'CHILDREN' => array ( 'CHILD_RATE' => array ( 'CHILD_QUANTITY' => '1', 'CHILD_AGE' => '5', ), ), ), 2 => array ( 'OCCUPANCY' => '8', 'QUANTITY' => 1, 'NO_OF_PASSENGERS' => 4, 'CHILDREN' => array ( 'CHILD_RATE' => array ( 'CHILD_QUANTITY' => '2', 'CHILD_AGE' => '5', ), ), ), 3 => array ( 'OCCUPANCY' => '6', 'QUANTITY' => 1, 'NO_OF_PASSENGERS' => 15, 'CHILDREN' => array ( 'CHILD_RATE' => array ( 'CHILD_QUANTITY' => '10', 'CHILD_AGE' => '5', ), ), ), 4 => array ( 'OCCUPANCY' => '5', 'QUANTITY' => 1, 'NO_OF_PASSENGERS' => 4, 'CHILDREN' => array ( 'CHILD_RATE' => array ( 'CHILD_QUANTITY' => '0', 'CHILD_AGE' => '5', ), ), ), 5 => array ( 'OCCUPANCY' => '1', 'QUANTITY' => 1, 'NO_OF_PASSENGERS' => 2, 'CHILDREN' => array ( 'CHILD_RATE' => array ( 'CHILD_QUANTITY' => '1', 'CHILD_AGE' => '5', ), ), ), 6 => array ( 'OCCUPANCY' => '4', 'QUANTITY' => 1, 'NO_OF_PASSENGERS' => 3, 'CHILDREN' => array ( 'CHILD_RATE' => array ( 'CHILD_QUANTITY' => '0', 'CHILD_AGE' => '5', ), ), ), 7 => array ( 'OCCUPANCY' => '2', 'QUANTITY' => 1, 'NO_OF_PASSENGERS' => 2, 'CHILDREN' => array ( 'CHILD_RATE' => array ( 'CHILD_QUANTITY' => '0', 'CHILD_AGE' => '5', ), ), ), ), ), 'VERSION_HISTORY' => array ( 'LANGUAGE' => 'en-GB', 'LICENCE_KEY' => 'A6C2FAAA-62D7-4A1B-9AB5-C6BF801E7803', ), 'ISMEALPLANSREQUIRED' => 0, 'IMAGENOTREQUIRED' => 1, 'ReturnMatchCode' => 'true', 'SEARCHWITHFACILITIES_OPTIONS' => 'ALL', 'NotesRequired' => false, 'SERVICEIDs' => '1228', 'START_DATE' => '10/10/2015', 'NUMBER_OF_NIGHTS' => 1, 'AVAILABLE_ONLY' => false, 'GET_START_PRICE' => true, 'CURRENCY' => 'USD', 'SERVICETYPEID' => 2, 'RETURN_ONLY_NON_ACCOM_SERVICES' => false, 'ROOM_REPLY' => array ( 'ANY_ROOM' => 'true', ), 'DoNotReturnNonRefundable' => false, 'DoNotReturnWithCancellationPenalty' => false, 'BESTSELLER' => false, 'CLIENT_ID' => 0, 'BOOKING_TYPE_ID' => 0, 'BOOKINGTYPE' => 0, 'PRICETYPE' => 0, 'SERVICETYPERATINGTYPEID' => 0, 'SERVICETYPERATINGID' => 0, 'IsServiceOptionDescriptionRequired' => 'true', 'IsServiceInfoRequired' => 'true', 'ReturnMandatoryExtraPrices' => false, 'NATIONALITYID' => 0, 'ReturnAttachedOptionExtra' => false, 'SERVICESEARCHTYPE' => 'ENHANCED', 'ReturnAppliedOptionChargingPolicyDetails' => false, ), );

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
            $response["GetServicesPricesAndAvailabilityResult"]["Errors"] = $validator->errors()->all();
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
