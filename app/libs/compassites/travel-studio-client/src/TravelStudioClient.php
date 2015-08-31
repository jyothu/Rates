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
class TravelStudioClient extends TravelStudioItinerary {

    private $currentServiceName = null;

    public function __construct(DateHelper $DateHelper, TsBookingIdPoolHelper $tsBookingIdPoolHelper, EnvironmentHelper $environmentHelper, ServiceRulesHelper $serviceRulesHelper) {
        parent::__construct($DateHelper, $tsBookingIdPoolHelper, $environmentHelper, $serviceRulesHelper);
    }

    function getServicePriceAndAvailability($serviceId, $serviceTypeName, $dateOnWhichServiceIsRequired = null, $nightsForWhichServiceIsRequired = null, $currencyCode = null, $serviceCheck = false, $region_id = null) {
        $serviceTypeId = $this->getServiceTypeIdFromServiceName($serviceTypeName, $serviceId);
        $includedServiceTsIds = array();
        $servicePrice = 0;
        $errorMsg = array();
        $responseIsSuccess = true;
        $serviceDetails = $this->getServiceTypeIdFromServiceName($serviceTypeName, $serviceId);
        $serviceTypeId = $serviceDetails['serviceTypeId'];
        $this->setServiceDetails($serviceId, $serviceTypeId, $dateOnWhichServiceIsRequired, $nightsForWhichServiceIsRequired, $currencyCode);
        $ServiceType = new \ServiceType();
        if ($serviceTypeName == 'service') {            
            foreach ($serviceDetails['service']->includeServices as $include_service) {
                $includedServiceTsIds[] = $include_service->include_service_tsid;
            }
        }

        ## Included Service - Bulking several services into one
        if (!empty($includedServiceTsIds)) {
            foreach ($includedServiceTsIds as $includedServiceTsId) {
                $serviceDetails = $this->getServiceTypeIdFromServiceName($serviceTypeName, $includedServiceTsId);
                $serviceTypeId = $serviceDetails['serviceTypeId'];
                $multiply_by_nights = $ServiceType->getOption($serviceTypeId)[0]['multiply_by_nights'];
                $nightsForIncludedService = $nightsForWhichServiceIsRequired;
                if (!$multiply_by_nights) {
                    $nightsForIncludedService = 1;
                }
                $servicePrice += $this->checkServiceAndBuildMultiRequestForHotel($includedServiceTsId, $serviceTypeId, $dateOnWhichServiceIsRequired, $nightsForIncludedService, $currencyCode, $serviceCheck, $region_id);
                if ($this->hasError()) {
                    $errorMsg[0] = $this->currentServiceName . ' :' .$this->responseErrorMsgs[0];
                    $responseIsSuccess = false;
                    //break;
                } elseif ($this->getErrorType() == 'warning') {
                    $errorMsg[0] = $this->currentServiceName . ' :' .$this->responseErrorMsgs[0];
                }
            }
            # Assign the consolidate values
            $this->responseIsSuccessfull = $responseIsSuccess;
            $this->responseErrorMsgs = $errorMsg;
            $this->servicePrice = $servicePrice;
        } else {
            if ($serviceTypeName == 'service') {
                $multiply_by_nights = $ServiceType->getOption($serviceTypeId)[0]['multiply_by_nights'];
                if (!$multiply_by_nights) {
                    $nightsForWhichServiceIsRequired = 1;
                }
            }

            $servicePrice = $this->checkServiceAndBuildMultiRequestForHotel($serviceId, $serviceTypeId, $dateOnWhichServiceIsRequired, $nightsForWhichServiceIsRequired, $currencyCode, $serviceCheck, $region_id);
        }
        $this->servicePrice = $servicePrice;
        return $this->servicePrice;
    }

    function checkServiceAndBuildMultiRequestForHotel($includedServiceTsId, $serviceTypeId, $dateOnWhichServiceIsRequired, $nightsForWhichServiceIsRequired, $currencyCode, $serviceCheck, $region_id = NULL) {
        $response = 0;
        $buying_price  =0;
        $rulesResult = array();
        $rulesCheckResult = array();
        $rulesCheckResult = $this->serviceRuleCheck($includedServiceTsId, $serviceTypeId, $dateOnWhichServiceIsRequired, $nightsForWhichServiceIsRequired, $region_id);
        $warningtype  = NULL;
        $rulesResult = $rulesCheckResult[0];
        $errorMessage[] = $this->serviceRulesHelper->prepareErrorMessages($rulesCheckResult);
        
        if ($rulesResult->response == 'error') {
            $this->responseErrorType = 'error';
            // $this->responseErrorMsgs =[''.$rulesResult->rule_name.' : '.$rulesResult->response_message.'']  ;
            $this->responseErrorMsgs = $errorMessage;
            $this->responseIsSuccessfull = false;
            return $this->servicePrice;
        } else if ($rulesResult->response == 'region_warning') {
            $this->responseIsSuccessfull = true;
            $this->responseErrorType = 'region_warning';
            $this->responseErrorMsgs = $errorMessage;
            return $this->servicePrice;
        } else if ($rulesResult->response == 'region_success') {
            $this->responseIsSuccessfull = true;
            $this->responseErrorType = 'region_success';
            $this->responseErrorMsgs = "";
            return $this->servicePrice = "";
        } else if ($rulesResult->response == 'warning') {

            $warningtype  = 'warning';
            $warning        =  $errorMessage;
        } else {
            $this->responseErrorType = "";
            $this->responseErrorMsgs = "";
        }
        if ($serviceTypeId == 2) {
            $this->totalBuyingPrice = 0;
            for ($i = 0; $i < $nightsForWhichServiceIsRequired; $i++) {
                if ($i > 0) {
                    $dateOnWhichServiceIsRequired = $this->dateHelper->getMySqlDateFromNormalDate($dateOnWhichServiceIsRequired);
                    $dateOnWhichServiceIsRequired = $this->dateHelper->addDaysToDate($dateOnWhichServiceIsRequired, 1);
                    $dateOnWhichServiceIsRequired = $this->dateHelper->getNormalDateFromMySqlDate($dateOnWhichServiceIsRequired);
                }
                $response += $this->checkAvailabilityAndGetPreviousYearPrice($includedServiceTsId, $serviceTypeId, $dateOnWhichServiceIsRequired, 1, $currencyCode, $serviceCheck, $region_id);                
                $buying_price += $this->gettotalBuyingPrice();
            }
             $this->totalBuyingPrice = $buying_price;
        } else {
            $response = $this->checkAvailabilityAndGetPreviousYearPrice($includedServiceTsId, $serviceTypeId, $dateOnWhichServiceIsRequired, $nightsForWhichServiceIsRequired, $currencyCode, $serviceCheck,$region_id);
        }
        if ($this->servicePrice && $warningtype) {
            $this->responseErrorType = $warningtype;
            $this->responseErrorMsgs = $warning;
        }
        return $response;
    }

    private function checkAvailabilityAndGetPreviousYearPrice($includedServiceTsId, $serviceTypeId, $dateOnWhichServiceIsRequired, $nightsForWhichServiceIsRequired, $currencyCode, $serviceCheck, $region_id = NULL) {
        $response = 0;
        $response = $this->getServicesPricesAndAvailability($includedServiceTsId, $serviceTypeId, $dateOnWhichServiceIsRequired, $nightsForWhichServiceIsRequired, $currencyCode, $serviceCheck,$region_id);
        if ($this->hasError() && (trim($this->responseErrorMsgs[0]) == 'Service Not Found')) {
            $this->responseErrorType = "";
            $this->responseErrorMsgs = "";
            $dateOnWhichServiceIsRequired = $this->dateHelper->getPreviousYearDateFromNormalDate($dateOnWhichServiceIsRequired);
            $response = $this->getServicesPricesAndAvailability($includedServiceTsId, $serviceTypeId, $dateOnWhichServiceIsRequired, $nightsForWhichServiceIsRequired, $currencyCode, $serviceCheck, $region_id = NULL);
            if (!$this->hasError()) {

                //$this->responseErrorType = 'warning';
                //$this->responseErrorMsgs = ['Price is fetched from previous year '];
                #TODO change to confi
                $this->servicePrice = $this->servicePrice * $this->incrementForPreviousYearServicePrice;
                $response = $this->servicePrice;
                # Log the price in db
                $servicePriceLog = new \ServicePriceLog();
                $servicePriceLog->service_tsid = $includedServiceTsId;
                $servicePriceLog->service_name = $this->currentServiceName;
                $servicePriceLog->service_type_id = $serviceTypeId;
                $servicePriceLog->currency = $currencyCode;
                $servicePriceLog->service_start_date = $this->dateHelper->getMySqlDateTimeFromNormalDate($dateOnWhichServiceIsRequired);
                $servicePriceLog->no_nights = $nightsForWhichServiceIsRequired;
                $servicePriceLog->price = $this->servicePrice;
                $servicePriceLog->save();
            }
        }
        if( !$this->hasError() && $this->servicePrice==0 &&$serviceTypeId==2)
        {
            $this->responseErrorType = 'warning';
            $this->responseErrorMsgs = [$this->currentServiceName . ' :  Service price is zero'];
        }

        return $response;
    }

    function getServiceTypeIdFromServiceName($serviceTypeName, $serviceId = null) {
//        $typeList = array('hotel' => 2, 'service' => 12, 'activity' => 3, 'internalservice' => 20);
        $service = '';
        if ($serviceId) {
            if ($serviceTypeName == 'activity') {
                $service = \Activity::where('activity_tsid', '=', $serviceId)->take(1)->get()[0];
                $serviceTypeId = $service['service_type'];
                $this->currentServiceName = $service['activity_name'];
            } elseif ($serviceTypeName == 'service') {
                $service = \Service::where('service_tsid', '=', $serviceId)->take(1)->get()[0];
                $serviceTypeId = $service['service_type'];
                $this->currentServiceName = $service['service_name'];
            } elseif ($serviceTypeName == 'hotel') {
                $service = \Hotels::where('hotel_tsid', '=', $serviceId)->take(1)->get()[0];
                $this->currentServiceName = $service['hotel_name'];
                $serviceTypeId = 2;
            } elseif ($serviceTypeName == 'internalservice') {
                $service = \InternalService::where('service_tsid', '=', $serviceId)->take(1)->get()[0];
                $serviceTypeId = $service['service_type'];
                $this->currentServiceName = $service['service_name'];
            } elseif ($serviceTypeName == 'city') {
                $serviceTypeId = 31;
            }
        }
        return array('serviceTypeId' => $serviceTypeId, 'service' => $service);
    }

    function saveTSBookingIntoTB($booking_id) {
        if (strpos($booking_id, "TM") === 0) {
            $itineraryObj = \Itinerary::where('ts_booking_id', '=', $booking_id)->take(1)->get()[0];
            $itineraryObj->load('internalServices');
            $itineraryObj->load('cities');
            $itineraryObj->cities->load('services');
            $itineraryObj->cities->load('activities');
            $itinerary = $itineraryObj->replicate();
            $itinerary->push();
        } else if (strpos($booking_id, "postid") === 0) {
            $tmpostid = substr($booking_id, 6);
            $itineraryObj = \Itinerary::where('tmpostid', '=', $tmpostid)->orderBy('itinerary_id', 'DESC')->first();
            $itineraryObj->load('internalServices');
            $itineraryObj->load('cities');
            $itineraryObj->cities->load('services');
            $itineraryObj->cities->load('activities');
            $itinerary = $itineraryObj->replicate();
            $itinerary->push();
        } else {
            $rawRes = $this->getBookingDataFromBookingID($booking_id, true);
            $parsedRes = $rawRes->BookingInfoResponses->ResponseList->anyType->enc_value;
            $itinerary = $this->saveItineraryDetailsFromTS($parsedRes);
            $services = $this->getFormattedBookingDetails($rawRes)['services'];
            $services = $this->removeCityNotAssignHotel($services);

            $services = $this->assignServicesToTheirRespectiveCity($services);

            //$services = $this->mergeCitiesRepeatingSequentially($services);
            //echo "<pre>"; print_r($services); echo "</pre>"; exit;
            $this->saveBookingInDb($services, $itinerary);
        }
        return $itinerary;
    }


}
