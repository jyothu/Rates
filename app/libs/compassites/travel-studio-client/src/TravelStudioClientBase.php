<?php

namespace Compassites\TravelStudioClient;

use Compassites\DateHelper\DateHelper;

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
    protected $dateOnWhichServiceIsRequired = '2015-05-01';
    protected $dateOnWhichServiceEnds = '2015-05-01';
    protected $nightsForWhichServiceIsRequired = '1';
    protected $currencyCode = "EUR";
    protected $responseIsSuccessfull = false;
    protected $responseErrorMsgs = array();
    protected $isServiceAvailable = false;
    protected $servicePrice = 0;
    protected $roomOccupancy = 2;
    protected $licenseKey = 'A6C2FAAA-62D7-4A1B-9AB5-C6BF801E7803';
    protected $response;
    protected $numberOfPassengers = 2;
    protected $numberOfChildren = 0;
    protected $quantity = 1;
    protected $validRoomTypesForTheHotel = array();
    protected $selectedRoomTypesForTheHotel = array();
    protected $findPriceForSelectedRoomTypes = false;
    public $requestArray = array();
    public $serviceOptions;
    public $parsedResponse;
    protected $client;
    public $defaultServiceOption = [];
    private $shouldLogPriceRequest = false;
    private $shouldLogExtrasRequest = false;
    protected $responseErrorType = '';
    public $hardStop = false;
    public $softStop = false;
    protected $isServiceNotFound = false;
    protected $incrementForPreviousYearServicePrice = 1.05;
    protected $perDayServcieOptionsArray = [];

    function getSoapClient()
    {
        $params = array(
            "soap_version" => SOAP_1_2,
            "trace" => 1,
            "exceptions" => 1,
        );
        $client = new \SoapClient('http://52.74.9.44/B2CWS/B2CXMLAPIWebService.asmx?WSDL', $params);
        return $client;
    }

    function getDefaultServiceOption($serviceOptions, $occupancy = null)
    {
        $leastPricedOption = [];
        $noOptionForGivenOccupancy = true;
        if (count($serviceOptions) > 0) {
            $leastPricedOption = $serviceOptions[0];
            $leastPrice = $serviceOptions[0]['TotalSellingPrice'];
            foreach ($serviceOptions as $serviceOption) {
                if ($occupancy == $serviceOption['Occupancy']) {
                    if ($leastPrice > $serviceOption['TotalSellingPrice']) {
                        $leastPrice = $serviceOption['TotalSellingPrice'];
                        $leastPricedOption = $serviceOption;
                        $noOptionForGivenOccupancy = false;
                    }
                }
            }
            if ($noOptionForGivenOccupancy) {
                if ($leastPrice > $serviceOption['TotalSellingPrice']) {
                    $leastPrice = $serviceOption['TotalSellingPrice'];
                    $leastPricedOption = $serviceOption;
                }
            }
        }
        return $leastPricedOption;
    }

    function getServicesPricesAndAvailability($serviceId, $serviceTypeId, $dateOnWhichServiceIsRequired, $nightsForWhichServiceIsRequired, $currencyCode, $limitToOneRoomType = false, $serviceCheck = true, $region_id = null)
    {
        $this->responseErrorMsgs = [];
        $this->responseErrorType = [];
        $this->serviceOptions = [];
        $this->defaultServiceOption = [];
        
        return $this->checkAvailabilityAndCalculateDayWisePriceForService($serviceId, $serviceTypeId, $dateOnWhichServiceIsRequired, $nightsForWhichServiceIsRequired, $currencyCode, $serviceCheck, $region_id, $limitToOneRoomType);
    }

    public function getSingleServicePriceAndAvailability($serviceId, $serviceTypeId, $dateOnWhichServiceIsRequired, $nightsForWhichServiceIsRequired, $currencyCode, $limitToOneRoomType = false)
    {

        if (count($this->responseErrorMsgs) > 0 && $this->responseErrorType == 'error') {
            $this->hardStop = true;
//            return 0;
        } elseif (count($this->responseErrorMsgs) > 0 && $this->responseErrorType) {
            $this->softStop = true;
        }
        if (!$dateOnWhichServiceIsRequired) {
            $dateOnWhichServiceIsRequired = $this->dateOnWhichServiceIsRequired;
        }
        if (!$nightsForWhichServiceIsRequired) {
            $nightsForWhichServiceIsRequired = $this->nightsForWhichServiceIsRequired;
        }
        $RETURN_ONLY_NON_ACCOM_SERVICES = true;
        if ($serviceTypeId == 2) {
            $RETURN_ONLY_NON_ACCOM_SERVICES = false;
        }
        if ($serviceTypeId == 2 && $this->findPriceForSelectedRoomTypes) {
            $reqAppend = $this->prepareRequestSeletedRoomTypes();
        } else {
            if ($limitToOneRoomType || $serviceTypeId != 2) {
                $reqAppend['IncomingRequest']['ROOMS_REQUIRED']['ROOM']['OCCUPANCY'] = $this->roomOccupancy;
                $reqAppend['IncomingRequest']['ROOMS_REQUIRED']['ROOM']['QUANTITY'] = $this->quantity;
                $reqAppend['IncomingRequest']['ROOMS_REQUIRED']['ROOM']['NO_OF_PASSENGERS'] = $this->numberOfPassengers;
                $reqAppend['IncomingRequest']['ROOMS_REQUIRED']['ROOM']['CHILDREN']['CHILD_RATE']['CHILD_QUANTITY'] = $this->numberOfChildren;
                $reqAppend['IncomingRequest']['ROOMS_REQUIRED']['ROOM']['CHILDREN']['CHILD_RATE']['CHILD_AGE'] = '5';
            } else {
                $reqAppend = $this->prepareRequestWithAllRoomTypes($this->quantity);
            }
        }
        if (!$currencyCode) {
            $currencyCode = $this->currencyCode;
        } else {
            $this->currencyCode = $currencyCode;
        }
        $req['IncomingRequest'] = array();
        if (count($reqAppend) > 0) {
            $req = array_merge($req, $reqAppend);
        }

        $req['IncomingRequest']['VERSION_HISTORY']['LANGUAGE'] = 'en-GB';
        $req['IncomingRequest']['VERSION_HISTORY']['LICENCE_KEY'] = $this->licenseKey;

        $req['IncomingRequest']['ISMEALPLANSREQUIRED'] = 0;
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
        $req['IncomingRequest']['ROOM_REPLY']['ANY_ROOM'] = 'true';

        $req['IncomingRequest']['DoNotReturnNonRefundable'] = false;
        $req['IncomingRequest']['DoNotReturnWithCancellationPenalty'] = false;

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
        $this->requestArray = $req;
        try {
            $result = $this->client->GetServicesPricesAndAvailability($req);
            $this->respose = $result;
            if ($this->shouldLogPriceRequest ) {               
                \Illuminate\Support\Facades\File::put(public_path() . "/../last_request.xml", $this->client->__getLastRequest());              
            }
        } catch (Exception $exc) {
            $this->responseErrorMsgs[] = $exc->getTraceAsString();
        }
        if (!$this->parseServicesPricesAndAvailabilityResponseForError($result)) {
            $this->serviceOptions = $this->getOptionsForService($result);
            $this->defaultServiceOption = $this->getDefaultServiceOption($this->serviceOptions, 2);
            return $this->parseServicesPricesAndAvailabilityResponseForData($result);
        } else {
            $this->softStop = false;
            $this->hardStop = true;
        }
    }

    function parseServicesPricesAndAvailabilityResponseForError($response)
    {
        $hasServicePriceAndAvailabilityKey = property_exists($response, 'GetServicesPricesAndAvailabilityResult');
        $hasErrorKeyCount = $hasServicePriceAndAvailabilityKey && property_exists($response->GetServicesPricesAndAvailabilityResult, 'Errors') && property_exists($response->GetServicesPricesAndAvailabilityResult->Errors, "Error");
        if ($hasErrorKeyCount || !$hasServicePriceAndAvailabilityKey) {
            $errorMsgs = array();
            foreach ($response->GetServicesPricesAndAvailabilityResult->Errors as $error) {
                if (property_exists($error, 'Description')) {
                    $errorMsgs[] = $error->Description;
                }
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
        if ($this->hasError() && (trim($this->responseErrorMsgs[0]) == 'Service Not Found')) {
            $this->isServiceNotFound = true;
        } else {
            $this->isServiceNotFound = false;
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
        $currency = $parsedRes->CurrencyISOcode;
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
        if (true || $this->isServiceAvailable()) {
            $priceArray = $response->GetServicesPricesAndAvailabilityResult->Services->PriceAndAvailabilityService->ServiceOptions->PriceAndAvailabilityResponseServiceOption;
            if (is_array($priceArray)) {
                $servicePrice = $priceArray[0]->TotalSellingPrice;
                foreach ($priceArray as $price) {
                    if ($price->TotalSellingPrice < $servicePrice) {
                        $servicePrice = $price->TotalSellingPrice;
                    }
                }
            } else {
                $servicePrice = $priceArray->TotalSellingPrice;
            }
            $this->servicePrice = $servicePrice;
        } else {
            $this->responseErrorMsgs = ["Service not available for the date"];
        }
        return $this->servicePrice;
    }

    function getSservicePrice()
    {
        return $this->servicePrice;
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

    public function getOptionsForService($response)
    {
        $serviceOptions = array();
        $this->hasValidServiceOption = count($response->GetServicesPricesAndAvailabilityResult->Services->PriceAndAvailabilityService->ServiceOptions->PriceAndAvailabilityResponseServiceOption) > 0;
        $PriceAndAvailabilityResponseServiceOptions = $response->GetServicesPricesAndAvailabilityResult->Services->PriceAndAvailabilityService->ServiceOptions->PriceAndAvailabilityResponseServiceOption;
        if (is_object($PriceAndAvailabilityResponseServiceOptions)) {
            $serviceOptions[] = $this->prepareDataForServcieOption($PriceAndAvailabilityResponseServiceOptions);
        } elseif (is_array($PriceAndAvailabilityResponseServiceOptions)) {
            foreach ($PriceAndAvailabilityResponseServiceOptions as $PriceAndAvailabilityResponseServiceOption) {
                $serviceOptions[] = $this->prepareDataForServcieOption($PriceAndAvailabilityResponseServiceOption);
            }
        }
        return $serviceOptions;
    }

    function prepareDataForServcieOption($PriceAndAvailabilityResponseServiceOption)
    {
        $serviceOption = array();
        $serviceOption['ServiceOptionName'] = $PriceAndAvailabilityResponseServiceOption->ServiceOptionName;
        $serviceOption['TotalSellingPrice'] = $PriceAndAvailabilityResponseServiceOption->TotalSellingPrice;
        $serviceOption['OptionID'] = $PriceAndAvailabilityResponseServiceOption->OptionID;
        $serviceOption["MaxAdult"] = $PriceAndAvailabilityResponseServiceOption->MaxAdult;
        $serviceOption["MaxChild"] = $PriceAndAvailabilityResponseServiceOption->MaxChild;
        $serviceOption["Occupancy"] = $PriceAndAvailabilityResponseServiceOption->Occupancy;
        if ($this->findPriceForSelectedRoomTypes) {
            foreach ($this->selectedRoomTypesForTheHotel as $selectedRoomTypesForTheHotel) {
                if ($selectedRoomTypesForTheHotel && ($selectedRoomTypesForTheHotel->OptionID == $serviceOption['OptionID'])) {
                    $serviceOption["quantity"] = $selectedRoomTypesForTheHotel->quantity;
                    $serviceOption["adultCount"] = $selectedRoomTypesForTheHotel->adultCount;
                    $serviceOption["childCount"] = $selectedRoomTypesForTheHotel->childCount;
                }
            }
        } else {
            $serviceOption["quantity"] = 0;
            $serviceOption["adultCount"] = 0;
            $serviceOption["childCount"] = 0;
        }
        return $serviceOption;
    }

    public function setNumberOfPassengers($numberOfPassengers, $childCount = 0)
    {
        if ($numberOfPassengers > 0) {
            $this->numberOfPassengers = $numberOfPassengers;
        }
        if ($childCount > 0) {
            $this->numberOfChildren = $childCount;
        }
    }

    public function setQuantity($quantity)
    {
        if ($quantity > 0) {
            $this->quantity = $quantity;
        }
    }

    function setRoomDetails($room_type_tsid, $quantity, $numberOfPassengers, $childCount)
    {
        $this->setQuantity($quantity);
        $this->setNumberOfPassengers($numberOfPassengers, $childCount);
        $this->setRoomType($room_type_tsid);
    }

    function getApiRequest()
    {
        return $this->client->__getLastRequest();
    }

    function getAllRoomTypes()
    {
        $req = array();
        $req['objRoomTypeRequest']['Authenticate'] = array('LICENSEKEY' => $this->licenseKey, 'PASSENGERID' => 0, 'Connector' => 'enmTS');
        $resp = $this->client->getRoomTypes($req);
        return $resp->RoomTypeResponse->RoomTypes->RoomType;
    }

    function prepareRequestWithAllRoomTypes($quantity = 1)
    {
        $req = array();
        foreach ($this->validRoomTypesForTheHotel as $i => $roomType) {
            $req['IncomingRequest']['ROOMS_REQUIRED']['ROOM'][$i]['OCCUPANCY'] = $roomType->room_type_tsid;
            $req['IncomingRequest']['ROOMS_REQUIRED']['ROOM'][$i]['QUANTITY'] = 1;
            $req['IncomingRequest']['ROOMS_REQUIRED']['ROOM'][$i]['NO_OF_PASSENGERS'] = $roomType->max_adult + $roomType->max_children;
            $req['IncomingRequest']['ROOMS_REQUIRED']['ROOM'][$i]['CHILDREN']['CHILD_RATE']['CHILD_QUANTITY'] = $roomType->max_children;
            $req['IncomingRequest']['ROOMS_REQUIRED']['ROOM'][$i]['CHILDREN']['CHILD_RATE']['CHILD_AGE'] = '5';
        }
        return $req;
    }

    function prepareRequestSeletedRoomTypes()
    {
        $req = array();
        foreach ($this->selectedRoomTypesForTheHotel as $i => $roomType) {
            if ($roomType && ((int) ($roomType->Occupancy) > 0)) {
                $req['IncomingRequest']['ROOMS_REQUIRED']['ROOM'][$i]['OCCUPANCY'] = $roomType->Occupancy;
                $req['IncomingRequest']['ROOMS_REQUIRED']['ROOM'][$i]['QUANTITY'] = $roomType->quantity;
                $req['IncomingRequest']['ROOMS_REQUIRED']['ROOM'][$i]['NO_OF_PASSENGERS'] = $roomType->adultCount + $roomType->childCount;
                $req['IncomingRequest']['ROOMS_REQUIRED']['ROOM'][$i]['CHILDREN']['CHILD_RATE']['CHILD_QUANTITY'] = $roomType->childCount;
                $req['IncomingRequest']['ROOMS_REQUIRED']['ROOM'][$i]['CHILDREN']['CHILD_RATE']['CHILD_AGE'] = '5';
            }
        }
        return $req;
    }

    public function setSelectedRoomTypesForTheHotel($selectedRoomTypesForTheHotel)
    {
        $this->findPriceForSelectedRoomTypes = true;
        $this->selectedRoomTypesForTheHotel = $selectedRoomTypesForTheHotel;
    }

    public function setRoomType($room_type_tsid)
    {
        if ($room_type_tsid > 0) {
            $this->roomOccupancy = $room_type_tsid;
        }
        $this->validRoomTypesForTheHotel = array();
    }

    public function setValidRoomTypesForTheHotel($validRoomTypesForTheHotel, $dontResetSelected = false)
    {
        $this->findPriceForSelectedRoomTypes = false;
        if (count($validRoomTypesForTheHotel) > 0) {
            $this->validRoomTypesForTheHotel = $validRoomTypesForTheHotel;
        }
        if (!$dontResetSelected) {
            $this->selectedRoomTypesForTheHotel = array();
        }
    }

    public function getValidRoomTypesForTheHotel()
    {
        return $this->validRoomTypesForTheHotel;
    }

    public function getExtrasForAService($servcieTypeId, $serviceId, $fromDate = null, $toDate = null, $currencyCode = null)
    {
        $apiResponse = null;
        $response = null;
        $req = array();
        if (!$fromDate) {
            $fromDate = $this->dateOnWhichServiceIsRequired;
        }
        if (!$toDate) {
            $toDate = $this->dateOnWhichServiceEnds;
        }
        if (is_array($servcieTypeId)) {
            $servcieTypeId = $servcieTypeId['service_type_id'];
        }
        if (!$servcieTypeId) {
            $servcieTypeId = $this->servcieTypeId;
        }
        if (!$currencyCode) {
            $currencyCode = $this->currencyCode;
        }
        if ($this->roomOccupancy > 0) {
            $roomOccupancy = $this->roomOccupancy;
        } else {
            $roomOccupancy = 2;
        }
        if ($this->numberOfPassengers > 0) {
            $adults = ($this->numberOfPassengers - $this->numberOfChildren);
        } else {
            $adults = 2;
        }
        if ($this->quantity > 0) {
            $quantity = $this->quantity;
        } else {
            $quantity = 1;
        }
        $req['IncomingRequest']['Authenticate'] = array('LICENSEKEY' => $this->licenseKey, 'PASSENGERID' => 0, 'Connector' => 'enmTS');
        $req['IncomingRequest']['BOOKING_TYPE_ID'] = 0;
        $req['IncomingRequest']['PRICE_TYPE_ID'] = 0;

        $req['IncomingRequest']['PriceCode'] = 0;
        $req['IncomingRequest']['SERVICEID'] = $serviceId;
        $req['IncomingRequest']['FROMDATE'] = $fromDate;
        $req['IncomingRequest']['TODATE'] = $toDate;
        $req['IncomingRequest']['ReturnLinkedServiceOptions'] = false;


        $req['IncomingRequest']['IGNORECHILDAGE'] = false;
        $req['IncomingRequest']['RETURNONLYNONACCOMODATIONSERVICES'] = true;
        $req['IncomingRequest']['APPLYEXCHANGERATES'] = true;
        $req['IncomingRequest']['CURRENCYISOCODE'] = $currencyCode;
        $req['IncomingRequest']['ClientId'] = 0;
        $req['IncomingRequest']['ReturnAppliedChargingPolicyDetails'] = true;
        if (true) {
            $req['IncomingRequest']['ExtrasRequired']['ExtraDetail']['OccupancyID'] = 1;
            $req['IncomingRequest']['ExtrasRequired']['ExtraDetail']['Quantity'] = $quantity;
            $req['IncomingRequest']['ExtrasRequired']['ExtraDetail']['Adults'] = $adults;
        }
        if ($servcieTypeId != 2) {
            $req['IncomingRequest']['VEHICLE']['AvailableOnly'] = true;
            $req['IncomingRequest']['VEHICLE']['ServiceTypeID'] = $servcieTypeId;
            $req['IncomingRequest']['VEHICLE']['IsRecommendedProduct'] = false;
            $req['IncomingRequest']['VEHICLE']['LargeLuggage'] = 0;
            $req['IncomingRequest']['VEHICLE']['SmallLuggage'] = 0;
        }
        try {
            $apiResponse = $this->client->GetServiceExtraPrices($req);
            if ($this->shouldLogExtrasRequest) {
                \Illuminate\Support\Facades\File::put(public_path() . "/../last_request.xml", $this->client->__getLastRequest());
            }
        } catch (SoapFault $sp) {
            $this->responseIsSuccessfull = true;
        }
        $this->parseExtrasForAServiceForErrors($apiResponse);
        if (!$this->hasError()) {
            $response = $this->parseExtrasForAServiceForData($apiResponse);
        }
        return $response;
    }

    public function parseExtrasForAServiceForErrors($apiResponse)
    {
        if (property_exists($apiResponse->ServiceExtrasAndPricesResponse, "Errors") && property_exists($apiResponse->ServiceExtrasAndPricesResponse->Errors, "Errors")) {
            $this->responseIsSuccessfull = false;
        } else {
            $this->responseIsSuccessfull = true;
        }
        return $apiResponse;
    }

    public function parseExtrasForAServiceForData($apiResponse)
    {
        $extras = array();
        $parsedResponse = array();
        if (property_exists($apiResponse->ServiceExtrasAndPricesResponse->ResponseList, 'ServiceExtras')) {
            if (count($apiResponse->ServiceExtrasAndPricesResponse->ResponseList->ServiceExtras) > 1) {
                $parsedResponse = $apiResponse->ServiceExtrasAndPricesResponse->ResponseList->ServiceExtras;
            } else {
                $parsedResponse[] = $apiResponse->ServiceExtrasAndPricesResponse->ResponseList->ServiceExtras;
            }
        }
        foreach ($parsedResponse as $i => $extra) {
            if ($extra && (property_exists($extra, 'ServiceTypeExtraName'))) {
                $extras[$i]['ServiceTypeExtraName'] = $extra->ServiceTypeExtraName;
                $extras[$i]['ServiceExtraId'] = $extra->ServiceExtraId;
                $extras[$i]['OccupancyTypeID'] = $extra->OccupancyTypeID;
                $extras[$i]['ServiceTypeTypeID'] = $extra->ServiceTypeTypeID;
                $extras[$i]['ServiceTypeTypeName'] = $extra->ServiceTypeTypeName;
                $extras[$i]['ExtraMandatory'] = $extra->ExtraMandatory;
                $extras[$i]['MaxChild'] = $extra->MaxChild;
                $extras[$i]['MaxAdults'] = $extra->MaxAdults;
                $extras[$i]['TOTALPRICE'] = ceil($extra->TOTALPRICE);
            }
        }
        $this->parsedResponse = $extras;
        return $extras;
    }

    function checkServiceAvailability($serviceId, $serviceTypeId, $dateOnWhichServiceIsRequired, $nightsForWhichServiceIsRequired, $currencyCode)
    {
        if ($this->responseIsSuccessfull && $this->optionId > 0) {
            $currentMySqlStartDate = $this->dateHelper->getMySqlDateFromNormalDate($dateOnWhichServiceIsRequired);
            $currentMySqlEndDate = $this->dateHelper->addDaysToDate($currentMySqlStartDate, $nightsForWhichServiceIsRequired);
            $currentMySqlStartDate = date('d-M-Y', strtotime($currentMySqlStartDate));
            $currentMySqlEndDate = date('d-M-Y', strtotime($currentMySqlEndDate));

            $req['IncomingRequest'] = array(
                'Authenticate' =>
                array(
                    'LICENSEKEY' => $this->licenseKey,
                    'PASSENGERID' => 0,
                    'Connector' => 'enmTSHotelAPI',
                ),
                'Currency' => 'INR',
                'TotalAmount' => 0,
                'BookingReference' => "EI58564",
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

    public function serviceRuleCheck($serviceId, $serviceTypeId, $dateOnWhichServiceIsRequired, $nightsForWhichServiceIsRequired, $region_id)
    {
        $data[0] = array('ts_id' => $serviceId,
            'service_type' => $serviceTypeId,
            'start_date' => $dateOnWhichServiceIsRequired,
            'nights' => $nightsForWhichServiceIsRequired,
            'region_id' => $region_id
        );
        $rulesResult = $this->serviceRulesHelper->getRulesBysingleService($data);
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

    function getErrorType()
    {
        return $this->responseErrorType;
    }

    function checkAvailabilityAndCalculateDayWisePriceForService($serviceTsId, $serviceTypeId, $dateOnWhichServiceIsRequired, $nightsForWhichServiceIsRequired, $currencyCode, $serviceCheck, $region_id = NULL, $limitToOneRoomType = false)
    {
        $errorMessage = null;
        $warningtype = null;
        $warning = null;
        $rulesCheckResult = $this->serviceRuleCheck($serviceTsId, $serviceTypeId, $dateOnWhichServiceIsRequired, $nightsForWhichServiceIsRequired, $region_id);
        $rulesResult = $rulesCheckResult[0];
        if ($errorMessages = $this->serviceRulesHelper->prepareErrorMessages($rulesCheckResult)) {
            $errorMessage[] = $errorMessages;
        }
        if ($rulesResult->response == 'error') {
            $this->responseErrorType = 'error';
            $this->responseErrorMsgs = $errorMessage;
            $this->responseIsSuccessfull = false;
        } else if ($rulesResult->response == 'region_warning') {
            $this->responseIsSuccessfull = true;
            $this->responseErrorType = 'region_warning';
            $this->responseErrorMsgs = $errorMessage;
        } else if ($rulesResult->response == 'region_success') {
            $this->responseIsSuccessfull = true;
            $this->responseErrorType = 'region_success';
            $this->responseErrorMsgs = "";
        } else if ($rulesResult->response == 'warning') {
            $warningtype = 'warning';
            $warning = $errorMessage;
        } else {
            $this->responseErrorType = "";
            $this->responseErrorMsgs = "";
        }
        $price = $this->getPriceForEachDaySeparatelyAndIfNotAvailableGetPreviousYearPrice($serviceTsId, $serviceTypeId, $dateOnWhichServiceIsRequired, $nightsForWhichServiceIsRequired, $currencyCode, $limitToOneRoomType, $serviceCheck, $region_id);
        if ($warningtype) {
            $this->responseErrorType = $warningtype;
            $this->responseErrorMsgs = $warning;
        }
        return $price;
    }

    function addWarningAndErrorToResponse()
    {
        $responseData = [];
        $responseData['hasError'] = $this->hardStop || $this->softStop;
        $responseData['isHardStop'] = $this->hardStop;
        $responseData['errorMsgs'] = $this->getErrorMsgs();
        $responseData['errorType'] = $this->getErrorType();
        return $responseData;
    }

    function getPriceForEachDaySeparatelyAndIfNotAvailableGetPreviousYearPrice($serviceTsId, $serviceTypeId, $startDateOnWhichServiceIsRequired, $nightsForWhichServiceIsRequired, $currencyCode, $limitToOneRoomType, $serviceCheck, $region_id)
    {
        $servicePrice = 0;
        $nightsForEachRequest = 1;
        for ($i = 0; $i < $nightsForWhichServiceIsRequired; $i++) {
            $dateOnWhichServiceIsRequired = $this->dateHelper->addDaysToNormalDate($startDateOnWhichServiceIsRequired, $i);
            $this->getSingleServicePriceAndAvailability($serviceTsId, $serviceTypeId, $dateOnWhichServiceIsRequired, $nightsForEachRequest, $currencyCode, $limitToOneRoomType);
            if ($this->isServiceNotFound) {
                $this->responseErrorType = "";
                $this->responseErrorMsgs = "";
                $dateOnWhichServiceIsRequired = $this->dateHelper->getPreviousYearDateFromNormalDate($dateOnWhichServiceIsRequired);
                $this->getSingleServicePriceAndAvailability($serviceTsId, $serviceTypeId, $dateOnWhichServiceIsRequired, $nightsForEachRequest, $currencyCode, $limitToOneRoomType);
                $servicePrice += $this->servicePrice * $this->incrementForPreviousYearServicePrice;
                $this->addServicePriceLogEntry($serviceTsId, $serviceTypeId, $dateOnWhichServiceIsRequired, $nightsForEachRequest, $currencyCode, $servicePrice);
            } else {
                $servicePrice += $this->servicePrice;
                $this->perDayServcieOptionsArray[] = $this->serviceOptions;
            }
            if (!$this->hasError() && $this->servicePrice == 0 && $serviceTypeId == 2) {
                $this->responseErrorType = 'warning';
                $currentServiceName = '';
                $this->responseErrorMsgs = [$currentServiceName . ' :  Service price is zero'];
            }
        }
        $this->updateServiceOptionPricesFetchedPerDayToTotalPrice();
        $this->servicePrice = $servicePrice;
        return $servicePrice;
    }

    function updateServiceOptionPricesFetchedPerDayToTotalPrice()
    {

        $totalPriceArrayForOption = [];
        foreach ($this->perDayServcieOptionsArray as $day => $perDayServcieOptions) {
            foreach ($perDayServcieOptions as $perDayServcieOption) {
                $totalPriceArrayForOption[$perDayServcieOption['OptionID']] = array_get($totalPriceArrayForOption, $perDayServcieOption['OptionID'], 0) + $perDayServcieOption['TotalSellingPrice'];
            }
        }
        foreach ($this->serviceOptions as $i => $serviceOption) {
            $this->serviceOptions[$i]['TotalSellingPrice'] = $totalPriceArrayForOption[$serviceOption['OptionID']];
        }
        $this->perDayServcieOptionsArray = [];
    }

    function addServicePriceLogEntry($serviceTsId, $serviceTypeId, $dateOnWhichServiceIsRequired, $nightsForWhichServiceIsRequired, $currencyCode, $servicePrice)
    {
        if (!$this->hasError()) {
            $servicePriceLog = new \ServicePriceLog();
            $servicePriceLog->service_tsid = $serviceTsId;
            $servicePriceLog->service_name = $this->getServiceName($serviceTsId, $serviceTypeId);
            $servicePriceLog->service_type_id = $serviceTypeId;
            $servicePriceLog->currency = $currencyCode;
            $servicePriceLog->service_start_date = $this->dateHelper->getMySqlDateTimeFromNormalDate($dateOnWhichServiceIsRequired);
            $servicePriceLog->no_nights = $nightsForWhichServiceIsRequired;
            $servicePriceLog->price = $servicePrice;
            $servicePriceLog->save();
        }
    }

    function getServiceName($serviceTsId, $serviceTypeId)
    {
        $currentServiceName = null;
        if ($serviceTsId) {
            if ($serviceTypeId == 3 || $serviceTypeId == 5 || $serviceTypeId == 30) {
                $service = \Activity::where('activity_tsid', '=', $serviceTsId)->first();
                $currentServiceName = $service->activity_name;
            } elseif ($serviceTypeId == 2) {
                $service = \Hotels::where('hotel_tsid', '=', $serviceTsId)->first();
                $currentServiceName = $service->hotel_name;
            } elseif ($serviceTypeId == 20 || $serviceTypeId == 24) {
                $service = \InternalService::where('service_tsid', '=', $serviceTsId)->first();
                $currentServiceName = $service->service_name;
            } else {
                $service = \Service::where('service_tsid', '=', $serviceTsId)->first();
                $currentServiceName = $service->service_name;
            }
        }
        return $currentServiceName;
    }

}
