<?php

namespace Compassites\TravelStudioClient;

use Compassites\DateHelper\DateHelper;
use Illuminate\Session\Store;
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
class TravelStudioClient extends TravelStudioItinerary
{

    public function __construct(DateHelper $DateHelper, Store $session, ServiceRulesHelper $serviceRulesHelper)
    {
        $this->session = $session;
        if ($this->session->has('itineraryData')) {
            $itineraryData = $this->session->get('itineraryData');
            $this->currencyCode = array_get($itineraryData, "itinerary.currency", $this->currencyCode);
        }
        $this->dateHelper = $DateHelper;
        $this->client = $this->getSoapClient();
        $this->serviceRulesHelper = $serviceRulesHelper;
    }

    function getServicePriceAndAvailability($serviceId, $serviceTypeName, $dateOnWhichServiceIsRequired = null, $nightsForWhichServiceIsRequired = null, $currencyCode = null, $limitToOneRoomType = false, $serviceCheck = false, $region_id = null)
    {
        $serviceTypeId = $this->getServiceTypeIdFromServiceName($serviceTypeName, $serviceId);
        $this->setServiceDetails($serviceId, $serviceTypeId, $dateOnWhichServiceIsRequired, $nightsForWhichServiceIsRequired, $currencyCode);
        $ServiceType = new \ServiceType();
        $multiply_by_nights = $ServiceType->getOption($serviceTypeId)[0]['multiply_by_nights'];
        if (!$multiply_by_nights) {
            $nightsForWhichServiceIsRequired = 1;
        }
        return $this->getServicesPricesAndAvailability($serviceId, $serviceTypeId, $dateOnWhichServiceIsRequired, $nightsForWhichServiceIsRequired, $currencyCode, $limitToOneRoomType, $serviceCheck, $region_id);
    }

    function getServiceTypeIdFromServiceName($serviceTypeName, $serviceId = null)
    {
//        $typeList = array('hotel' => 2, 'service' => 12, 'activity' => 3, 'internalservice' => 20);
        if ($serviceId) {
            if ($serviceTypeName == 'activity') {
                $serviceTypeId = \Activity::where('activity_tsid', '=', $serviceId)->take(1)->get()[0]['service_type'];
            } elseif ($serviceTypeName == 'service') {
                $serviceTypeId = \Service::where('service_tsid', '=', $serviceId)->take(1)->get()[0]['service_type'];
            } elseif ($serviceTypeName == 'hotel') {
                $serviceTypeId = 2;
            } elseif ($serviceTypeName == 'internalservice') {
                $serviceTypeId = \InternalService::where('service_tsid', '=', $serviceId)->take(1)->get()[0]['service_type'];
            }
            $this->serviceTypeId = $serviceTypeId;
        }
        return $serviceTypeId;
    }

    function saveTSBookingIntoTB($booking_id)
    {
        $rawRes = $this->getBookingDataFromBookingID($booking_id, true);
        $parsedRes = $rawRes->BookingInfoResponses->ResponseList->anyType->enc_value;
        $itinerary = $this->saveItineraryDetailsFromTS($parsedRes);
        $services = $this->getFormattedBookingDetails($rawRes)['services'];
        $this->saveBookingInDb($services, $itinerary);
        return $itinerary;
    }

    function getServiceTypeId()
    {
        return $this->serviceTypeId;
    }
}
