<?php

namespace Compassites\PriceCalculation;

use Compassites\TravelStudioClient\TravelStudioClient;
use Compassites\DateHelper\DateHelper;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of priceCalculation
 *
 * @author senthil
 */
class PriceCalculation {

    private $priceResponseError;
    private $priceResponseErrorMessage;
   // private $priceResponseWarn;
    private $priceResponseWarnMessage;
    private $priceResponseWarn = array();
    /*
     *  @name  getCityItineraryPrice
     *  @param array
     *  @return array
     */

    public function __construct(TravelStudioClient $travelStudioClient, DateHelper $dateHelper) {
        $this->travelStudioClient = $travelStudioClient;
        $this->dateHelper = $dateHelper;
        $this->priceResponseError   = array();
        //$this->priceResponseWarn  =  array();
    }

    function getCityItineraryPrice($data) {
        $price1 = 0;
        $price2 = 0;
        if (!empty($data) && $data['city']) {
            $servicePrice1 = $data['city']['service_total_price'];
            $servicePrice2 = $data['city']['service2_total_price'];
            $price1 += $servicePrice1;
            $price1 += $data['city']['hotel_price'];

            $price2 += ((empty($data['city']['hotel2'])) ?
                            $servicePrice1 : $servicePrice2);
            $price2 += ((empty($data['city']['hotel2'])) ?
                            $data['city']['hotel_price'] : $data['city']['hotel2_price']);


            if (!empty($data['activities'])) {
                $price1 += array_sum($data['activities']['price']);
                $price2 += array_sum($data['activities']['price']);
            }

            if (!empty($data['guides'])) {
                $price1 += array_sum($data['guides']['price']);
                $price2 += array_sum($data['guides']['price']);
            }
        }
        $result = array('price1' => $price1, 'price2' => $price2);

        return $result;
    }

    public function getAdjustablePrice($currency = 'USD', $amount) {

        $currency = strtoupper($currency);
        $multiplier = \GeneralSetting::where('currency', '=', $currency)->take(1)->get()[0]['multiplier'];

        return $amount * $multiplier;
    }

    public function reArrageItinerarayDates($itinerary, $data) {
        $this->priceResponseError = array();
        $this->priceResponseWarn  =  array();
        $price1 = 0;
        $price2 = 0;

        $previousCityEndDate = $data['itinerary']['start-date'];
        $currencyCode = $data['itinerary']['currency'];
        # Internal service Id
        $internalServicePriceList = $this->getTravelServicePrices($data['itinerary']['internalservice_id'], 'internalservice', $data['itinerary']['start-date'], 1, $currencyCode);
        $data['itinerary']['internalservice_price'] = array_sum($internalServicePriceList);
        $data['itinerary']['internalservice_price_list'] = implode("|", $internalServicePriceList);

        $price1 = $data['itinerary']['internalservice_price'];
        $price2 = $price1;
        # Adjustment 
        $price1 += $data['itinerary']['adjustment1'];
        $price2 += $data['itinerary']['adjustment2'];

        $i = 0;
        foreach ($data['city'] as $cityKey => $cityDetails) {

            $noOfNights = $cityDetails['city']['nights'];
            $city = $cityDetails['city'];
            $cityOrderNumber = $i;

            $cityDates = $itinerary->getDatesForAccomodationFromItenaryData($previousCityEndDate, $noOfNights, $previousCityEndDate);
            $data['city'][$i]['city']['start-date'] = $cityDates['start-date'];
            $data['city'][$i]['city']['end-date'] = $cityDates['end-date'];
            $previousCityEndDate = $cityDates['end-date'];

            $cityEndDate = $data['city'][$i]['city']['end-date'];
            $cityStartDate = $data['city'][$i]['city']['start-date'];
            # Activities  prices
            if (!empty($cityDetails['activities'])) {
                foreach ($cityDetails['activities']['activity_id'] as $activitiesKey => $activityID) {
                    if (!empty($activityID)) {
                        $fromDate = $data['city'][$i]['activities']['from_date'][$activitiesKey];
                        $noOfNights = $data['city'][$i]['activities']['nights'][$activitiesKey];
                        if (!$this->validateActivityDate($cityStartDate, $cityEndDate, $fromDate, $noOfNights)) {
                            $data['city'][$i]['activities']['from_date'][$activitiesKey] = $cityStartDate;
                            $fromDate = $cityStartDate;
                        }
                    }
                }
            }
            #
            # Guides  prices
            if (!empty($cityDetails['guides'])) {
                foreach ($cityDetails['guides']['activity_id'] as $activitiesKey => $activityID) {
                    if (!empty($activityID)) {
                        $fromDate = $data['city'][$i]['guides']['from_date'][$activitiesKey];
                        $noOfNights = $data['city'][$i]['guides']['nights'][$activitiesKey];
                        if (!$this->validateActivityDate($cityStartDate, $cityEndDate, $fromDate, $noOfNights)) {
                            $data['city'][$i]['guides']['from_date'][$activitiesKey] = $cityStartDate;
                            $fromDate = $cityStartDate;
                        }
                    }
                }
            }
            
            $i++;
        }
        $data['itinerary']['previous-end-date'] = $previousCityEndDate;      

        return $data;
    }

    public function reCalculateItinerarayPrices($itinerary, $data) {
        $this->priceResponseError = array();
        $this->priceResponseWarn  =  array();
        $price1 = 0;
        $price2 = 0;

        $previousCityEndDate = $data['itinerary']['start-date'];
        $currencyCode = $data['itinerary']['currency'];
        # Internal service Id
        $internalServicePriceList = $this->getTravelServicePrices($data['itinerary']['internalservice_id'], 'internalservice', $data['itinerary']['start-date'], 1, $currencyCode);
        $data['itinerary']['internalservice_price'] = array_sum($internalServicePriceList);
        $data['itinerary']['internalservice_price_list'] = implode("|", $internalServicePriceList);

        $price1 = $data['itinerary']['internalservice_price'];
        $price2 = $price1;
        # Adjustment 
        $price1 += $data['itinerary']['adjustment1'];
        $price2 += $data['itinerary']['adjustment2'];

        $i = 0;
        foreach ($data['city'] as $cityKey => $cityDetails) {
            
            
            $noOfNights = $cityDetails['city']['nights'];
            $city = $cityDetails['city'];
            $cityOrderNumber = $i;
              
            $cityDates = $itinerary->getDatesForAccomodationFromItenaryData($previousCityEndDate, $noOfNights, $previousCityEndDate);
            $data['city'][$i]['city']['start-date'] = $cityDates['start-date'];
            $data['city'][$i]['city']['end-date'] = $cityDates['end-date'];
            $previousCityEndDate = $cityDates['end-date'];
            $this->getTravelServicePrices($city['id'], 'city', $cityDates['start-date'], $noOfNights, $currencyCode, $cityOrderNumber,$city['id']);            
            # Travel Service Price calculation
            $travelServicePriceList = $this->getTravelServicePrices($city['service_id'], 'service', $cityDates['start-date'], $noOfNights, $currencyCode, $cityOrderNumber);
            $serviceTotalPrice = array_sum($travelServicePriceList);
            $data['city'][$i]['city']['service_total_price'] = $serviceTotalPrice;
            $data['city'][$i]['city']['service_price'] = implode("|", $travelServicePriceList);
            #
            # Hotel Price calculation
            $hotelPrice = $this->getServicePrice($city['hotel_id'], 'hotel', $cityDates['start-date'], $noOfNights, $currencyCode, $cityOrderNumber);
            $data['city'][$i]['city']['hotel_price'] = $hotelPrice;
            #
            # Option 2 Travel Service Price calculation
            $travelServicePriceList = $this->getTravelServicePrices($city['service2_id'], 'service', $cityDates['start-date'], $noOfNights, $currencyCode, $cityOrderNumber);
            $service2TotalPrice = array_sum($travelServicePriceList);
            $data['city'][$i]['city']['service2_total_price'] = $service2TotalPrice;
            $data['city'][$i]['city']['service2_price'] = implode("|", $travelServicePriceList);
            #
            # Option 2 Hotel Price calculation
            $hotel2Price = $this->getServicePrice($city['hotel2_id'], 'hotel', $cityDates['start-date'], $noOfNights, $currencyCode, $cityOrderNumber);
            $data['city'][$i]['city']['hotel2_price'] = $hotel2Price;
            #

            $cityEndDate = $data['city'][$i]['city']['end-date'];
            $cityStartDate = $data['city'][$i]['city']['start-date'];
            # Activities  prices
            $activitiesPrices = 0;
            if (!empty($cityDetails['activities'])) {
                foreach ($cityDetails['activities']['activity_id'] as $activitiesKey => $activityID) {
                    if (!empty($activityID)) {
                        $fromDate = $data['city'][$i]['activities']['from_date'][$activitiesKey];
                        $noOfNights = $data['city'][$i]['activities']['nights'][$activitiesKey];
                        if (!$this->validateActivityDate($cityStartDate, $cityEndDate, $fromDate, $noOfNights)) {
                            $data['city'][$i]['activities']['from_date'][$activitiesKey] = $cityStartDate;
                            $fromDate = $cityStartDate;
                        }

                        $data['city'][$i]['activities']['price'][$activitiesKey] = $this->getServicePrice($activityID, 'activity', $fromDate, $noOfNights, $currencyCode, $cityOrderNumber);
                        $activitiesPrices += $data['city'][$i]['activities']['price'][$activitiesKey];
                    }
                }
            }
            #
            # Guides  prices
            $guidesPrices = 0;
            if (!empty($cityDetails['guides'])) {
                foreach ($cityDetails['guides']['activity_id'] as $activitiesKey => $activityID) {
                    if (!empty($activityID)) {
                        $fromDate = $data['city'][$i]['guides']['from_date'][$activitiesKey];
                        $noOfNights = $data['city'][$i]['guides']['nights'][$activitiesKey];
                        if (!$this->validateActivityDate($cityStartDate, $cityEndDate, $fromDate, $noOfNights)) {
                            $data['city'][$i]['guides']['from_date'][$activitiesKey] = $cityStartDate;
                            $fromDate = $cityStartDate;
                        }
                        $data['city'][$i]['guides']['price'][$activitiesKey] = $this->getServicePrice($activityID, 'activity', $fromDate, $noOfNights, $currencyCode, $cityOrderNumber);
                        $guidesPrices += $data['city'][$i]['guides']['price'][$activitiesKey];
                    }
                }
            }

            # Price
            $price1 += $serviceTotalPrice + $hotelPrice + $activitiesPrices + $guidesPrices;

            if (!empty($city['hotel2_id'])) {
                $price2 += $service2TotalPrice + $hotel2Price + $activitiesPrices + $guidesPrices;
            } else {
                $price2 += $serviceTotalPrice + $hotelPrice + $activitiesPrices + $guidesPrices;
            }

            $i++;
        }
        $data['itinerary']['previous-end-date'] = $previousCityEndDate;
        $data['price1'] = $this->getAdjustablePrice($data['itinerary']['currency'], $price1);
        $data['price2'] = $this->getAdjustablePrice($data['itinerary']['currency'], $price2);

        return $data;
    }

    private function validateActivityDate($cityStartDate, $cityEndDate, $activityStartDate, $activityNights = 0) {
        $result = false;
        if (!empty($activityStartDate)) {
            $activityEndDate = str_replace('-', '/', $activityStartDate);
            $activityEndDate = date('Y-m-d', strtotime($activityEndDate . "+1 days"));
            $cityStartDate = $this->dateHelper->getMySqlDateFromNormalDate($cityStartDate);
            $cityEndDate = $this->dateHelper->getMySqlDateFromNormalDate($cityEndDate);
            $activityStartDate = $this->dateHelper->getMySqlDateFromNormalDate($activityStartDate);

            if ($cityStartDate <= $activityStartDate && $cityEndDate >= $activityEndDate) {
                $result = true;
            }
        }

        return $result;
    }

    private function getTravelServicePrices($serviceIds, $serviceTypeName, $startDate, $noNights, $currencyCode, $cityOrderNumber = 0,$region_id =NULL) {

        $travelServicePriceList = array();
        if (!empty($serviceIds)) {
            $travelServiceIdList = explode("|", $serviceIds);
            foreach ($travelServiceIdList as $travelServiceIdKey => $travelServiceId) {
                $travelServicePriceList[] = $this->getServicePriceFromTSAPI($travelServiceId, $serviceTypeName, $startDate, $noNights, $currencyCode, true,$region_id);
                $this->setLoggedError($cityOrderNumber, $travelServiceId, $serviceTypeName);
            }
        }

        return $travelServicePriceList;
    }

    private function getServicePrice($serviceId, $serviceTypeName, $startDate, $noNights, $currencyCode, $cityOrderNumber = 0) {

        $servicePrice = 0;
        if (!empty($serviceId)) {
            $servicePrice = $this->getServicePriceFromTSAPI($serviceId, $serviceTypeName, $startDate, $noNights, $currencyCode, true);
            $this->setLoggedError($cityOrderNumber, $serviceId, $serviceTypeName);
        }

        return $servicePrice;
    }

    private function getServicePriceFromTSAPI($serviceId, $serviceTypeName, $dateOnWhichServiceIsRequired = null, $nightsForWhichServiceIsRequired = null, $currencyCode = null, $serviceCheck=false,$region_id =NULL) {
        $price = 0;
        $this->priceResponseErrorMessage = '';
        $this->priceResponseWarnMessage='';
        $travelStudio = $this->travelStudioClient;
        $travelStudio->getServicePriceAndAvailability($serviceId, $serviceTypeName, $dateOnWhichServiceIsRequired, $nightsForWhichServiceIsRequired, $currencyCode, $serviceCheck,$region_id);

        if (!$travelStudio->hasError()) {
            $price = $travelStudio->getSservicePrice();
            if($travelStudio->getErrorType() == 'warning' ||$travelStudio->getErrorType() == 'region_warning'){
                $this->priceResponseWarnMessage = $travelStudio->getErrorMsgs();
            }            
        } else {
                $this->priceResponseErrorMessage = $travelStudio->getErrorMsgs();
        }

        return $price;
    }

    function calculateOptionPricesForItineraryWithoutApiCall($itinerary) {
        $internalServicePrice = 0;
        $hotel1Price = 0;
        $hotel2Price = 0;
        $servicePrice = 0;
        $activityPrice = 0;

        $internalServices = $itinerary->internalServices;
        foreach ($internalServices as $internalService) {
            $internalServicePrice+=$internalService->service_price;
        }
        $cities = $itinerary->cities;
        foreach ($cities as $city) {
            $hotel1Price+=$city->hotel1_price;
            $hotel2Price+=$city->hotel2_price;
            foreach ($city->services as $service) {
                $servicePrice+=$service->service_price;
            }
            foreach ($city->activities as $activity) {
                $activityPrice+=$activity->activity_price;
            }
        }
        $total = $internalServicePrice + $servicePrice + $activityPrice;
        $totalPrice['option1'] = $total + $hotel1Price;
        $totalPrice['option2'] = $total + $hotel2Price;
        return $totalPrice;
    }

    function setLoggedError($cityId, $serviceId, $serviceType) {
        if (!empty($this->priceResponseErrorMessage) && $serviceId > 0) {
            $this->priceResponseError[$cityId][$serviceType][$serviceId][] = $this->priceResponseErrorMessage;
        }
        if (!empty($this->priceResponseWarnMessage) && $serviceId > 0) {
            $this->priceResponseWarn[$cityId][$serviceType][$serviceId][] = $this->priceResponseWarnMessage;
        }
    }

    function getLoggedError() {
        return $this->priceResponseError;
        
    }
     function getLoggedwarning() {
        return $this->priceResponseWarn;
        
    }
    
}
