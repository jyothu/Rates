<?php

namespace Compassites\TravelStudioClient;

use Compassites\DateHelper\DateHelper;
use Compassites\TsBookingIdPoolHelper\TsBookingIdPoolHelper;
use Compassites\EnvironmentHelper\EnvironmentHelper;
use Compassites\ServiceRulesHelper\ServiceRulesHelper;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of TravelStudioClient
 *
 * @author jeevan
 */
class TravelStudioClientBase
{

    protected $serviceTypeId = '2';
    protected $serviceId;
    protected $dateOnWhichServiceIsRequired = '2015-03-01';
    protected $dateOnWhichServiceEnds = '2015-03-01';
    protected $nightsForWhichServiceIsRequired = '1';
    protected $currencyCode = "EUR";
    protected $responseIsSuccessfull = false;
    protected $responseErrorMsgs = array();
    protected $isServiceAvailable = false;
    protected $servicePrice = 0;
    protected $roomOccupancy = 2;
    protected $licenseKey = 'AC2FAAA-62D7-4A1B-9AB5-C6BF801E7803';
    protected $tsApiUrl = 'http://52.74.9.44/B2CWS/B2CXMLAPIWebService.asmx?WSDL';
    protected $response;
    private $optionId = 0;
    private $arrivalDetailsTypeArray = array(4, 6, 8, 9, 12, 13, 14, 21);
    private $allowServiceTypeForExtra =array(3, 20, 24);
    protected $responseErrorType ='';
    protected $incrementForPreviousYearServicePrice = 1.05;

    public function __construct(DateHelper $DateHelper, TsBookingIdPoolHelper $tsBookingIdPoolHelper, EnvironmentHelper $environmentHelper,ServiceRulesHelper $serviceRulesHelper)
    {
        $this->dateHelper = $DateHelper;
        $this->dateHelper = $DateHelper;
        $this->serviceRulesHelper = $serviceRulesHelper;
        $this->tsBookingIdPoolHelper = $tsBookingIdPoolHelper;
        if ($environmentHelper->hasEnvironment()) {
            $this->licenseKey = $environmentHelper->tsLicenseKey;
            $this->tsApiUrl = $environmentHelper->tsApiEndpoint;
        }
        $this->client = $this->getSoapClient();
    }

    function getSoapClient()
    {
        $params = array(
            "soap_version" => SOAP_1_2,
            "trace" => 1,
            "exceptions" => 0,
            'content-type' => 'text/xml; charset=utf-8'
        );
        $client = new \SoapClient($this->tsApiUrl, $params);
        return $client;
    }

    function getServicesPricesAndAvailability($serviceId, $serviceTypeId, $dateOnWhichServiceIsRequired, $nightsForWhichServiceIsRequired, $currencyCode, $serviceCheck = false,$region_id=null)
    {
        $this->responseErrorMsgs= [];
        $this->responseErrorType = [];
        $HOTEL_MEAL_PLAN_REQUIRED = 0;
         if (!$dateOnWhichServiceIsRequired) {
            $dateOnWhichServiceIsRequired = $this->dateOnWhichServiceIsRequired;
        }
        if (!isset($nightsForWhichServiceIsRequired)) {
            $nightsForWhichServiceIsRequired = $this->nightsForWhichServiceIsRequired;
        }
        $RETURN_ONLY_NON_ACCOM_SERVICES = true;
        if ($serviceTypeId == 2) {
            $RETURN_ONLY_NON_ACCOM_SERVICES = false;
            $HOTEL_MEAL_PLAN_REQUIRED = 1;
        }

        $req['IncomingRequest'] = array();
        $req['IncomingRequest']['VERSION_HISTORY']['LANGUAGE'] = 'en-GB';
        $req['IncomingRequest']['VERSION_HISTORY']['LICENCE_KEY'] = $this->licenseKey;

        $req['IncomingRequest']['ISMEALPLANSREQUIRED'] = $HOTEL_MEAL_PLAN_REQUIRED;
        $req['IncomingRequest']['IMAGENOTREQUIRED'] = 1;
        $req['IncomingRequest']['ReturnMatchCode'] = 'true';
        $req['IncomingRequest']['SEARCHWITHFACILITIES_OPTIONS'] = 'ALL';
        $req['IncomingRequest']['NotesRequired'] = false;
        $req['IncomingRequest']['SERVICEIDs'] = $serviceId;
        $req['IncomingRequest']['START_DATE'] = $dateOnWhichServiceIsRequired;
        $req['IncomingRequest']['NUMBER_OF_NIGHTS'] = $nightsForWhichServiceIsRequired;
        $req['IncomingRequest']['AVAILABLE_ONLY'] = false;
        $req['IncomingRequest']['GET_START_PRICE'] = true;
        $req['IncomingRequest']['CURRENCY'] = $currencyCode;
        $req['IncomingRequest']['SERVICETYPEID'] = $serviceTypeId;

        $req['IncomingRequest']['RETURN_ONLY_NON_ACCOM_SERVICES'] = $RETURN_ONLY_NON_ACCOM_SERVICES;
        $req['IncomingRequest']['ROOM_REPLY']['ALL_ROOM'] = true;

        $req['IncomingRequest']['DoNotReturnNonRefundable'] = false;
        $req['IncomingRequest']['DoNotReturnWithCancellationPenalty'] = false;

        $req['IncomingRequest']['ROOMS_REQUIRED']['ROOM']['OCCUPANCY'] = $this->roomOccupancy;
        $req['IncomingRequest']['ROOMS_REQUIRED']['ROOM']['QUANTITY'] = 1;
        $req['IncomingRequest']['ROOMS_REQUIRED']['ROOM']['NO_OF_PASSENGERS'] = 2;

        $req['IncomingRequest']['BESTSELLER'] = false;

        $req['IncomingRequest']['CLIENT_ID'] = 0;
        $req['IncomingRequest']['BOOKING_TYPE_ID'] = 0;
        $req['IncomingRequest']['BOOKINGTYPE'] = 0;
        $req['IncomingRequest']['PRICETYPE'] = 0;

        if ($serviceTypeId != 2) {
            $req['IncomingRequest']['SERVICETYPERATINGTYPEID'] = 0;
            $req['IncomingRequest']['SERVICETYPERATINGID'] = 0;
        }

        $req['IncomingRequest']['SERVICETYPERATINGTYPEID'] = 0;
        $req['IncomingRequest']['SERVICETYPERATINGID'] = 0;
        $req['IncomingRequest']['IsServiceOptionDescriptionRequired'] = 'true';
        $req['IncomingRequest']['IsServiceInfoRequired'] = 'true';
        $req['IncomingRequest']['ReturnMandatoryExtraPrices'] = false;

        $req['IncomingRequest']['NATIONALITYID'] = 0;


        $req['IncomingRequest']['ReturnAttachedOptionExtra'] = false;
        $req['IncomingRequest']['SERVICESEARCHTYPE'] = 'ENHANCED';
        $req['IncomingRequest']['ReturnAppliedOptionChargingPolicyDetails'] = false;
        try {
            $result = $this->client->GetServicesPricesAndAvailability($req);
        } catch (Exception $exc) {

            $this->responseErrorMsgs[] = $exc->getTraceAsString();
        }
        if (!$this->parseServicesPricesAndAvailabilityResponseForError($result)) {

            $this->parseServicesPricesAndAvailabilityResponseForData($result);
            /* Diabling the check service availability feature all across
             * if ($serviceCheck) {
                if (!in_array($serviceTypeId, $this->arrivalDetailsTypeArray)) $this->checkServiceAvailability($serviceId, $serviceTypeId, $dateOnWhichServiceIsRequired, $nightsForWhichServiceIsRequired, $currencyCode);
            }*/
            $this->getServiceExtraPrices($serviceId, $serviceTypeId, $dateOnWhichServiceIsRequired, $nightsForWhichServiceIsRequired, $currencyCode);
            return $this->servicePrice;
        }
    }

    function parseServicesPricesAndAvailabilityResponseForError($response)
    {
        $hasServicePriceAndAvailabilityKey = property_exists($response, 'GetServicesPricesAndAvailabilityResult');
        $hasErrorKeyCount = $hasServicePriceAndAvailabilityKey && property_exists($response->GetServicesPricesAndAvailabilityResult, 'Errors') && property_exists($response->GetServicesPricesAndAvailabilityResult->Errors, "Error");
        if ($hasErrorKeyCount || !$hasServicePriceAndAvailabilityKey) {
            $errorMsgs = array();
            foreach ($response->GetServicesPricesAndAvailabilityResult->Errors as $error) {
                $errorMsgs[] = $error->Description;
            }
            if (!$hasServicePriceAndAvailabilityKey) {
                $errorMsgs[] = "Bad response";
            }
            $this->responseErrorMsgs = $errorMsgs;
            $this->responseErrorType = 'error';
            $this->responseIsSuccessfull = false;
        } else {
            $this->responseIsSuccessfull = true;
        }
        return !$this->responseIsSuccessfull;
    }

    function parseBookingDetailsResponseForError($response)
    {
        $hasServicePriceAndAvailabilityKey = property_exists($response, 'BookingInfoResponses');
        $hasErrorKeyCount = $hasServicePriceAndAvailabilityKey && property_exists($response->BookingInfoResponses, 'ErrorList') && property_exists($response->BookingInfoResponses->ErrorList, "anyType") && property_exists($response->BookingInfoResponses->ErrorList->anyType, "enc_stype");
        if ($hasErrorKeyCount || !$hasServicePriceAndAvailabilityKey) {
            $errorMsgs = array();
            if ($response->BookingInfoResponses->ErrorList->anyType->enc_stype == "Error") {
                $errorMsgs[] = $response->BookingInfoResponses->ErrorList->anyType->enc_value->ErrorDescription;
            }
            if (!$hasServicePriceAndAvailabilityKey) {
                $errorMsgs[] = "Bad response";
            }
            $this->responseErrorMsgs = $errorMsgs;
        } else {
            $this->responseIsSuccessfull = true;
        }
        return !$this->responseIsSuccessfull;
    }

    function parseAndSaveBookingDetailsResponseForData($parsedResponse)
    {
        $bookedServiceList = $parsedResponse->BookedServices->BookedService;
        $outPut = array();
        foreach ($bookedServiceList as $key => $bookedService) {
            $ServiceTypeID = $bookedService->ServiceTypeID;
            $ServiceID = $bookedService->ServiceID;
            if (is_array($bookedService->BookedOptions->BookedOption)) {
                $BookedOption = $bookedService->BookedOptions->BookedOption[0];
            } else {
                $BookedOption = $bookedService->BookedOptions->BookedOption;
            }
            $FromDate = $this->dateHelper->removeTimeFromTMDate($BookedOption->FromDate);
            $ToDate = $this->dateHelper->removeTimeFromTMDate($BookedOption->ToDate);
            $RegionID = $bookedService->RegionID;
            $ServiceCostAmount = $bookedService->ServiceCostAmount;
            $outPut[] = array(
                'ServiceID' => $ServiceID,
                'RegionID' => $RegionID,
                'ServiceTypeID' => $ServiceTypeID,
                'FromDate' => $FromDate,
                'ToDate' => $ToDate,
                'ServiceCostAmount' => $ServiceCostAmount,
            );
        }
        return $outPut;
    }

    function saveItineraryDetailsFromTS($parsedRes)
    {
        $status = 1;
        $itinerary = new \Itinerary();
        $start_date = $this->dateHelper->removeTimeFromTMDate($parsedRes->BookingStartDate);
        $end_date = $this->dateHelper->removeTimeFromTMDate($parsedRes->BookingEndDate);
        $currency = $this->currencyCode;
        $created_by = $parsedRes->SECONDARY_SYSTEM_USER_NAME;
        $itinerary_name = $parsedRes->BookingReference;
        $number_of_nights = $this->dateHelper->dateDifferenceInDays($end_date, $start_date);
        $ts_booking_id = $parsedRes->BookingReference;
        $option1_price = $option2_price = 0;
        $Itinerary = $itinerary->saveItinerary(null, $status, $created_by, $start_date, $end_date, $currency, $itinerary_name, $number_of_nights, $option1_price, $option2_price, $ts_booking_id);
        return $Itinerary;
    }

    function isServiceAvailable()
    {
        return $this->isServiceAvailable;
    }

    function parseServicesPricesAndAvailabilityResponseForData($response)
    {
        $this->servicePrice = 0;
        $this->totalBuyingPrice = 0;
        $this->hotelDefaultOPtions = null;
        if (true || $this->isServiceAvailable()) {
            $priceArray = $response->GetServicesPricesAndAvailabilityResult->Services->PriceAndAvailabilityService->ServiceOptions->PriceAndAvailabilityResponseServiceOption;
            if (is_array($priceArray)) {
                $servicePrice = $priceArray[0]->TotalSellingPrice;
                $totalBuyingPrice = $priceArray[0]->TotalBuyingPrice;
                $hotelDefaultOPtions = $priceArray[0];
                $this->optionId = $priceArray[0]->OptionID;
                foreach ($priceArray as $price) {
                    if ($price->TotalSellingPrice < $servicePrice) {
                        $servicePrice = $price->TotalSellingPrice;
                        $totalBuyingPrice = $price->TotalBuyingPrice;
                        $this->optionId = $price->OptionID;
                        $hotelDefaultOPtions = $price;
                    }
                }
            } else {
                $servicePrice = $priceArray->TotalSellingPrice;
                $this->optionId = $priceArray->OptionID;
                $totalBuyingPrice = $priceArray->TotalBuyingPrice;
                $hotelDefaultOPtions = $priceArray;
            }
            $this->servicePrice     = $servicePrice;
            $this->totalBuyingPrice = $totalBuyingPrice;
            $this->hotelDefaultOPtions = $hotelDefaultOPtions;
        } else {
            $this->responseErrorMsgs = ["Service not available for the date"];
        }

        return $this->servicePrice;
    }

    function getSservicePrice()
    {
        return $this->servicePrice;
    }
    function gettotalBuyingPrice()
    {
        return $this->totalBuyingPrice;
    }

    function gethotelDefaultOPtions()
    {
        return $this->hotelDefaultOPtions;
    }

     function getErrorType()
    {
        return $this->responseErrorType;
    }

    function hasError()
    {
        return ($this->responseIsSuccessfull ) === true ? false : true;
    }

    function getErrorMsgs()
    {
        return count($this->responseErrorMsgs) > 0 ? $this->responseErrorMsgs : null;
    }

    function setServiceDetails($serviceId, $serviceTypeId, $dateOnWhichServiceIsRequired = null, $nightsForWhichServiceIsRequired = null, $currencyCode = null)
    {
        if ($dateOnWhichServiceIsRequired) {
            $this->dateOnWhichServiceIsRequired = $dateOnWhichServiceIsRequired;
        }
        if ($nightsForWhichServiceIsRequired) {
            $this->nightsForWhichServiceIsRequired = $nightsForWhichServiceIsRequired;
        }
        if ($currencyCode) {
            $this->currencyCode = $currencyCode;
        }
        $this->serviceId = $serviceId;
        $this->serviceTypeId = $serviceTypeId;
    }

    function addDaysToDates($fromDate, $numberOfDays)
    {
        return date($fromDate, strtotime("+$numberOfDays days"));
    }

    function getBookingDataFromBookingID($bookingID, $isRaw = false)
    {
        $req = array();
        $req['RequestObject']['Authenticate'] = array('LICENSEKEY' => $this->licenseKey, 'PASSENGERID' => 0, 'Connector' => 'enmTS');
        $req['RequestObject']['BookingReferenceNumber'] = $bookingID;
        $req['RequestObject']['CurrencyISOCode'] = $this->currencyCode;
        $req['RequestObject']['UseBookingCurrency'] = 0;
        try {
            $result = $this->client->GetBookingInfo($req);
        } catch (Exception $exc) {
            $this->responseErrorMsgs[] = $exc->getTraceAsString();
        }
        if (!$this->parseBookingDetailsResponseForError($result)) {
            return $isRaw ? $result : $result->BookingInfoResponses->ResponseList->anyType->enc_value;
        }
    }

    public function getApiResponse()
    {
        return $this->response;
    }

    public function getAllServicetypes()
    {
        $req = array();
        $req['Req']['Authenticate'] = array('LICENSEKEY' => $this->licenseKey, 'PASSENGERID' => 0, 'Connector' => 'enmTS');
        try {
            $result = $this->client->getServiceTypes($req);
            $this->respose = $result;
        } catch (Exception $exc) {
            $this->responseErrorMsgs[] = $exc->getTraceAsString();
        }
        return $serviceTypes = $result->getServiceTypesResult->ServiceTypes->ServiceType;
    }

    public function updateServiceTypes()
    {
        foreach ($this->getAllServicetypes() as $serviceType) {
            $ServiceTypeName = trim($serviceType->ServiceTypeName);
            $serviceTypeObj = \ServiceType::where('service_type_name', '=', "$ServiceTypeName")->first();
            if ($serviceTypeObj) {
                $serviceTypeObj->ts_service_type_id = trim($serviceType->ServiceTypeID);
            } else {
                $serviceTypeObj = new \ServiceType();
                $serviceTypeObj->service_type_name = $ServiceTypeName;
                $serviceTypeObj->ts_service_type_id = trim($serviceType->ServiceTypeID);
            }
            $serviceTypeObj->save();
        }
    }

    function getServiceExtraPrices($serviceId, $serviceTypeId, $dateOnWhichServiceIsRequired, $nightsForWhichServiceIsRequired, $currencyCode)
    {
        $this->servicePrice;

        if ( in_array($serviceTypeId, $this->allowServiceTypeForExtra)) {
            $req['IncomingRequest']['Authenticate'] = array(
                'LICENSEKEY' => $this->licenseKey,
                'PASSENGERID' => 0,
                'Connector' => 'enmTSHotelAPI'
            );

            $currentMySqlStartDate = $this->dateHelper->getMySqlDateFromNormalDate($dateOnWhichServiceIsRequired);

            $currentMySqlEndDate = $this->dateHelper->addDaysToDate($currentMySqlStartDate, $nightsForWhichServiceIsRequired);

            $req['IncomingRequest']['BOOKING_TYPE_ID'] = 0;
            $req['IncomingRequest']['PRICE_TYPE_ID'] = 0;
            $req['IncomingRequest']['VEHICLE']['AvailableOnly'] = true;
            $req['IncomingRequest']['VEHICLE']['ServiceTypeID'] = $serviceTypeId;
            $req['IncomingRequest']['VEHICLE']['IsRecommendedProduct'] = false;
            $req['IncomingRequest']['VEHICLE']['LargeLuggage'] = 0;
            $req['IncomingRequest']['VEHICLE']['SmallLuggage'] = 0;

            $req['IncomingRequest']['PriceCode'] = 0;
            $req['IncomingRequest']['SERVICEID'] = $serviceId;
            $req['IncomingRequest']['FROMDATE'] = $currentMySqlStartDate;
            $req['IncomingRequest']['TODATE'] = $currentMySqlEndDate;
            $req['IncomingRequest']['ReturnLinkedServiceOptions'] = false;

            $req['IncomingRequest']['ExtrasRequired']['ExtraDetail']['OccupancyID'] = $this->roomOccupancy;
            $req['IncomingRequest']['ExtrasRequired']['ExtraDetail']['Quantity'] = 1;
            $req['IncomingRequest']['ExtrasRequired']['ExtraDetail']['Adults'] = 2;

            $req['IncomingRequest']['IGNORECHILDAGE'] = false;
            $req['IncomingRequest']['RETURNONLYNONACCOMODATIONSERVICES'] = true;
            $req['IncomingRequest']['APPLYEXCHANGERATES'] = true;
            $req['IncomingRequest']['CURRENCYISOCODE'] = $currencyCode;
            $req['IncomingRequest']['ClientId'] = 0;
            $req['IncomingRequest']['ReturnAppliedChargingPolicyDetails'] = true;
            try {
                $result = $this->client->GetServiceExtraPrices($req);
                $this->servicePrice += $this->parsingExtraPriceResponse($result);
            } catch (Exception $exc) {
                //
            }
        }
        return $this->servicePrice;
    }

    function parsingExtraPriceResponse($respose)
    {
        $serviceExtraCost = 0;
        if (!empty($respose->ServiceExtrasAndPricesResponse) && $respose->ServiceExtrasAndPricesResponse->ResponseList && isset($respose->ServiceExtrasAndPricesResponse->ResponseList->ServiceExtras)) {
            $serviceExtras = $respose->ServiceExtrasAndPricesResponse->ResponseList->ServiceExtras;
            if (is_array($serviceExtras)) {
                foreach ($serviceExtras as $serviceExtraKey => $serviceExtra) {
                    $serviceExtraCost += $this->checkExtraMandatory($serviceExtra);
                }
            } else {
                $serviceExtraCost += $this->checkExtraMandatory($serviceExtras);
            }
        }

        return $serviceExtraCost;
    }

    function checkExtraMandatory($serviceExtra)
    {
        $serviceExtraCost = 0;
        if ($serviceExtra->ExtraMandatory == 1) {
            $serviceExtraCost += $serviceExtra->TOTALPRICE;
        }

        return $serviceExtraCost;
    }

    function checkServiceAvailability($serviceId, $serviceTypeId, $dateOnWhichServiceIsRequired, $nightsForWhichServiceIsRequired, $currencyCode)
    {

        if ($this->responseIsSuccessfull && $this->optionId > 0) {
            $currentMySqlStartDate = $this->dateHelper->getMySqlDateFromNormalDate($dateOnWhichServiceIsRequired);
            $currentMySqlEndDate = $this->dateHelper->addDaysToDate($currentMySqlStartDate, $nightsForWhichServiceIsRequired);
            $currentMySqlStartDate = date('d-M-Y', strtotime($currentMySqlStartDate));

            $currentMySqlEndDate = date('d-M-Y', strtotime($currentMySqlEndDate));

            /*if (in_array($serviceTypeId, $this->arrivalDetailsTypeArray)) {
                $currentMySqlEndDate = $currentMySqlStartDate;
            }
             *
             */

            $req['IncomingRequest'] = array(
                'Authenticate' =>
                array(
                    'LICENSEKEY' => $this->licenseKey,
                    'PASSENGERID' => 0,
                    'Connector' => 'enmTSHotelAPI',
                ),
                'Currency' => 'INR',
                'TotalAmount' => 0,
                'BookingReference' => $this->tsBookingIdPoolHelper->getTsBookingIdFromSession(),
                'ClientID' => 0,
                'BookingStatusID' => 0,
                'UpdateAction' => 'None',
                'UIPassengers' =>
                array(
                    'UIPassenger' =>
                    array(
                        array(
                            'PaxID' => 1,
                            'Title' => 'Miss',
                            'FirstName' => 'Kiran',
                            'LastName' => 'Vaze',
                            'Age' => 22,
                            'isMainPassenger' => true,
                            'isChild' => false,
                            'SMCPassengerid' => 10368,
                            'IsDelete' => false,
                            'IsAmend' => false,
                            'OccupancyTypeID' => $this->roomOccupancy,
                            'LogicalRoomID' => 1,
                            'GenderID' => 2,
                            'Intials' => 'K',
                            'NationalityTypeID' => 9,
                            'PassportNumber' => 0,
                            'TravelingWithInfant' => false,
                            'GDSPaxID' => 0,
                            'Type' => 0,
                        ),
                        array(
                            'PaxID' => 2,
                            'Title' => 'Miss',
                            'FirstName' => 'Kiran1',
                            'LastName' => 'Vaze1',
                            'Age' => 22,
                            'isMainPassenger' => false,
                            'isChild' => false,
                            'IsDelete' => false,
                            'IsAmend' => false,
                            'OccupancyTypeID' => $this->roomOccupancy,
                            'LogicalRoomID' => 1,
                            'GenderID' => 2,
                            'NationalityTypeID' => 9,
                            'PassportNumber' => 0,
                            'TravelingWithInfant' => false,
                            'GDSPaxID' => 0,
                            'Type' => 0,
                        ),
                    )
                ),
                'UIBookableServices' =>
                array(
                    'UIBookableService' =>
                    array(
                        'ServiceCode' => $serviceId,
                        'ServiceID' => $serviceId,
                        'CheckInDate' => $currentMySqlStartDate,
                        'CheckOutDate' => $currentMySqlEndDate,
                        'AvailableOnly' => false,
                        'ServiceRegionID' => 0,
                        'UIBookableOptions' =>
                        array(
                            'UIBookableOption' =>
                            array(
                                'RoomID' => 1,
                                'OptionID' => $this->optionId,
                                'SellPriceID' => 0,
                                'SellPrice' => 0,
                                'OccupancyTypeID' => $this->roomOccupancy,
                                'OptionCheckInDate' => $currentMySqlStartDate,
                                'OptionCheckOutDate' => $currentMySqlEndDate,
                                'PAXIDS' =>
                                array(
                                    'PAXID' =>
                                    array(
                                        array(
                                            'PassengerID' => 1,
                                        ),
                                        array(
                                            'PassengerID' => 2,
                                        ),
                                    ),
                                ),
                                'IsDelete' => false,
                                'IgnoreOptionId' => false,
                                'Quantity' => 1,
                                'ConsiderOptionQty' => false,
                                'IsAmend' => true,
                                'BookedOptionID' => 0,
                                'BookedOptionStatusID' => 0,
                                'AllocationAction' => 'TakeAllocation',
                            ),
                        ),
                        'IsDelete' => false,
                        'IsAmend' => false,
                    )
                ),
                'ProcessingMode' => 0,
                'PerformManualPaxAssignment' => true,
                'NationalityID' => 0,
                'RetainOriginalPrices' => false,
                'ApplyOffers' => false,
                'UseBookingROEOnly' => false,
                'BookingRequirements' => true,
                'CheckDuplicates' => false,
            );
            $result = $this->client->AmendBooking($req);
            //echo "REQUEST:\n" . $this->client->__getLastRequest() . "\n";
            if (strpos($result->getMessage(), ' Local Invalid Operation-The null value cannot be assigned to a member with type System.Boolean') === false) {
                $this->responseErrorMsgs = [$result->getMessage()];
                $this->responseIsSuccessfull = false;
            }
        }

        if ($this->optionId == 0) {
            $this->responseErrorMsgs = ["Service option is not found"];
            $this->responseIsSuccessfull = false;
        }

        return $this->responseIsSuccessfull;
    }

    public  function  serviceRuleCheck($serviceId, $serviceTypeId, $dateOnWhichServiceIsRequired, $nightsForWhichServiceIsRequired,$region_id)
    {
        $data[0] = array('ts_id'=>$serviceId,
                              'service_type'=> $serviceTypeId,
                              'start_date'=> $dateOnWhichServiceIsRequired,
                              'nights'=> $nightsForWhichServiceIsRequired,
                              'region_id'=>$region_id
                            );
        $rulesResult  = $this->serviceRulesHelper->getRulesBysingleService($data);
        return $rulesResult;
    }

    public function getBuyCurrency1($serviceId)
    {
        $req['ServiceInfoRequest']['Authenticate'] = array(
                'LICENSEKEY' => $this->licenseKey,
                'PASSENGERID' => 0,
                'Connector' => 'enmTSHotelAPI'
            );
        $req['ServiceInfoRequest']['ServiceId'] = $serviceId;
        $req['ServiceInfoRequest']['IsRatingDataRequired'] = false;

        try {
            $result = $this->client->GetServiceInformation1($req);
        } catch (Exception $ex) {
            return [];
        }
        return $result;
    }

    public function getBuyCurrency($serviceId)
    {
        $result = $this->getBuyCurrency1($serviceId);
        if (!is_null($result)) {
            return $result->ServiceInformationResponse->CurrencyID;
        }
        return 1;
    }
}
