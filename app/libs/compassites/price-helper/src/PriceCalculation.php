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
class PriceCalculation
{

    private $priceResponseError;
    private $priceResponseErrorMessage;
    private $priceResponseWarnMessage;
    private $priceResponseWarn = array();

    public function __construct(TravelStudioClient $travelStudioClient, DateHelper $dateHelper, \GeneralSetting $generalSetting)
    {
        $this->travelStudioClient = $travelStudioClient;
        $this->dateHelper = $dateHelper;
        $this->generalSetting = $generalSetting;
    }

    function getCityItineraryPrice($data)
    {
        $price1 = 0;
        $price2 = 0;
        if (!empty($data)) {

            if (!empty($data['optionone'])) {
                if (!empty($data['optionone']['arrivalDetail'])) {
                    $price1 += $this->getServiceTotalPrice($data['optionone']['arrivalDetail']);
                }
                if (!empty($data['optionone']['hotel'])) {
                    $price1 += $data['optionone']['hotel']['selectedHotelPrice'];
                }
            }
            if (!empty($data['optiontwo'])) {
                if (!empty($data['optiontwo']['arrivalDetail'])) {
                    $price2 += $this->getServiceTotalPrice($data['optiontwo']['arrivalDetail']);
                }
                if (!empty($data['optiontwo']['hotel'])) {
                    $price2 += $data['optiontwo']['hotel']['selectedHotelPrice'];
                }
            } else {
                $price1 = $price2;
            }
        }
        $result = array('price1' => $price1, 'price2' => $price2);
        return $result;
    }

    private function getServiceTotalPrice($data)
    {

        $price = 0;
        foreach ($data as $dataKey => $dataValue) {
            $price +=$dataValue['servicePrice'];
        }

        return $price;
    }

    public function getOccupancyIdFromPassengerCount($adult, $child = 0)
    {

        $occupancyId = 2;
        if ($adult > 0) {
            $occupancy = \HotelRoomType::where("max_adult", "=", $adult)->where("max_children", "=", $child)->first();
            if ($child > 0 && !$occupancy) {
                $occupancy = \HotelRoomType::where("max_adult", "=", $adult)->where("max_children", ">=", $child)->orderBy('max_children', 'ASC')->first();
                if (!$occupancy) {
                    $occupancy = \HotelRoomType::where("max_adult", ">=", $adult)->where("max_children", ">=", $child)->orderBy('max_adult', 'ASC')->first();
                }
            }
            if (!$occupancy) {
                $occupancy = \HotelRoomType::where("max_adult", "=", $adult)->first();
            }
            if (!$occupancy) {
                $occupancy = \HotelRoomType::where("max_adult", ">", $adult)->orderBy('max_adult', 'ASC')->first();
            }
            if ($occupancy) {
                $occupancyId = $occupancy->room_type_tsid;
            }
        }

        return $occupancyId;
    }

    private function buildPramForGetServicePrice($serviceId, $serviceTypeName, $startDate, $nights, $currencyCode, $optionList, $cityKey = 0)
    {
        if (!is_array($optionList[0])) {
            $optionList[0] = [];
        }
        if (!is_array($optionList[1])) {
            $optionList[1] = [];
        }

        return $this->getServicePrice($serviceId, $serviceTypeName, $startDate, $nights, $currencyCode, $optionList[0], $optionList[1], $cityKey);
    }

    public function reCalculateItinerarayPrices($itinerary, $data)
    {

        $this->priceResponseError = array();
        $this->priceResponseWarn = array();
        $adult = $data['itinerary']['adult'];
        $child = $data['itinerary']['child'];

        $defaultQuantity = 1;
        //echo "<pre>";
        $price1 = 0;
        $price2 = 0;
        // echo json_encode($data);exit;
        $previousCityEndDate = $data['itinerary']['startdate'];
        $currencyCode = $data['itinerary']['currency'];

        // otherDetails
        if (!empty($data['itinerary']['otherDetails'])) {

            $otherDetails = $data['itinerary']['otherDetails'];

            // echo json_encode($otherDetails);   exit;
            # Internal service Id
            if (!empty($otherDetails['internalService'])) {
                foreach ($otherDetails['internalService'] as $serviceKey => $service) {
                    if (!empty($service['internalService']['serviceOptions'])) {
                        $adultCount = array_get($service, 'guests.adult', 0);
                        $childCount = array_get($service, 'guests.child', 0);
                        $occupancyIdInternalService = $this->getOccupancyIdFromPassengerCount($adultCount, $childCount);
                        $this->travelStudioClient->setRoomDetails($occupancyIdInternalService, $defaultQuantity, $adultCount + $childCount, $childCount);
                        $optionsList = array();
                        $optionsList[0] = (!empty($service['internalService']['selectedServiceOptions'])) ? $service['internalService']['selectedServiceOptions'] : '';
                        $optionsList[1] = (!empty($service['internalService']['selectedServiceExtra'])) ? $service['internalService']['selectedServiceExtra'] : '';
                        $otherDetails['internalService'][$serviceKey]['price'] = $this->buildPramForGetServicePrice(
                                $service['internalServicesOptions']['service_tsid'], 'internalservice', $service['startDate'], $service['nights'], $currencyCode, $optionsList
                        );
                        $data['internalService'][$serviceKey]['price'] = $otherDetails['internalService'][$serviceKey]['price'];
                        $data['itinerary']['otherDetails']['internalService'][$serviceKey]['price'] = $otherDetails['internalService'][$serviceKey]['price'];
                        $price1 += $otherDetails['internalService'][$serviceKey]['price'];
                    }
                }
            }
            $occupancyId = $this->getOccupancyIdFromPassengerCount($adult, $child);
            $this->travelStudioClient->setRoomDetails($occupancyId, $defaultQuantity, $adult + $child, $child);
            # carService
            if (!empty($otherDetails['carService'])) {
                foreach ($otherDetails['carService'] as $serviceKey => $service) {
                    $optionsList = array();
                    $optionsList[0] = (!empty($service['carOptions']['selectedServiceOptions'])) ? $service['carOptions']['selectedServiceOptions'] : '';
                    $optionsList[1] = (!empty($service['carOptions']['selectedServiceExtra'])) ? $service['carOptions']['selectedServiceExtra'] : '';
                    $otherDetails['carService'][$serviceKey]['price'] = $this->buildPramForGetServicePrice(
                            $service['carserviceOptions']['service_tsid'], 'service', $service['startDate'], $service['nights'], $currencyCode, $optionsList
                    );
                    $data['carService'][$serviceKey]['price'] = $otherDetails['carService'][$serviceKey]['price'];
                    $price1 += $otherDetails['carService'][$serviceKey]['price'];
                }
            }
            $tourManager = $this->getPricesForTourManager($otherDetails, $data, $price1, $currencyCode);
            $data = $tourManager['data'];
            $price1 = $tourManager['price'];
            # tour Manger
        }
        $occupancyId = $this->getOccupancyIdFromPassengerCount($adult, $child);
        $this->travelStudioClient->setRoomDetails($occupancyId, $defaultQuantity, $adult + $child, $child);
        $price2 = $price1;
        //echo "<br> others = " . $price2;
        ## Cities
        $activitiesPrices = 0;
        $guidesPrices = 0;

        foreach ($data['city'] as $cityKey => $city) {

            $cityNights = $city['nightCount'];
            ## Date calculation 
            $cityDates = $itinerary->getDatesForAccomodationFromItenaryData($previousCityEndDate, $cityNights, $previousCityEndDate);
            $data['city'][$cityKey]['start-date'] = $cityDates['start-date'];
            $data['city'][$cityKey]['end-date'] = $cityDates['end-date'];
            $previousCityEndDate = $cityDates['end-date'];
            ##

            $cityStartDate = $data['city'][$cityKey]['start-date'];
            $cityEndDate = $data['city'][$cityKey]['end-date'];

            ## Option 1
            if (!empty($city['optionone'])) {
                ## Hotel 
                $hotelPriceData = $this->calculateHotelPriceWithOptions($city, 'optionone', $data, $cityStartDate, $cityNights, $currencyCode, $cityKey, $price1);
                $price1 = $hotelPriceData['price'];
                $data = $hotelPriceData['data'];
                ## Transfer
                $transferDetailsPriceData = $this->calculateTransferDetailsPriceWithOptions($city, 'optionone', $data, $cityStartDate, $cityNights, $currencyCode, $cityKey, $occupancyId, $defaultQuantity, $adult, $child, $price1);
                $price1 = $transferDetailsPriceData['price'];
                $data = $transferDetailsPriceData['data'];
            }
            ## Option 2            
            if (!empty($city['optiontwo'])) {
                $hotelPriceData = $this->calculateHotelPriceWithOptions($city, 'optiontwo', $data, $cityStartDate, $cityNights, $currencyCode, $cityKey, $price2);
                $price2 = $hotelPriceData['price'];
                $data = $hotelPriceData['data'];
                ## Transfer
                $transferDetailsPriceData = $this->calculateTransferDetailsPriceWithOptions($city, 'optiontwo', $data, $cityStartDate, $cityNights, $currencyCode, $cityKey, $occupancyId, $defaultQuantity, $adult, $child, $price2);
                $price2 = $transferDetailsPriceData['price'];
                $data = $transferDetailsPriceData['data'];
            }
            $this->travelStudioClient->setRoomDetails($occupancyId, $defaultQuantity, $adult + $child, $child);
            # Activities  prices
            if (!empty($city['activities'])) {
                $services = $city['activities'];
                foreach ($services as $serviceKey => $service) {
                    # validation against city start date
                    if (!$this->validateActivityDate($cityStartDate, $cityEndDate, $service['startDate'], $service['nights'])) {
                        $data['city'][$cityKey]['activities'][$serviceKey]['startDate'] = $cityStartDate;
                        $service['startDate'] = $cityStartDate;
                    }

                    $optionsList = array($service['selectedServiceOptions'], $service['selectedServiceExtra']);
                    $data['city'][$cityKey]['activities'][$serviceKey]['price'] = $this->buildPramForGetServicePrice(
                            $service['activity']['activity_tsid'], 'activity', $service['startDate'], $service['nights'], $currencyCode, $optionsList, $cityKey
                    );

                    $activitiesPrices += $data['city'][$cityKey]['activities'][$serviceKey]['price'];
                }
            }
            # Guides  prices
            if (!empty($city['guides'])) {
                $services = $city['guides'];
                foreach ($services as $serviceKey => $service) {
                    # validation against city start date
                    if (!$this->validateActivityDate($cityStartDate, $cityEndDate, $service['startDate'], $service['nights'])) {
                        $data['city'][$cityKey]['guides'][$serviceKey]['startDate'] = $cityStartDate;
                        $service['startDate'] = $cityStartDate;
                    }

                    $optionsList = array($service['selectedServiceOptions'], $service['selectedServiceExtra']);
                    $data['city'][$cityKey]['guides'][$serviceKey]['price'] = $this->buildPramForGetServicePrice(
                            $service['activity']['activity_tsid'], 'activity', $service['startDate'], $service['nights'], $currencyCode, $optionsList, $cityKey
                    );
                    $guidesPrices += $data['city'][$cityKey]['guides'][$serviceKey]['price'];
                }
            }
        }
        ## Price
        $data['price1'] = round($price1 + $activitiesPrices + $guidesPrices);
        $data['price2'] = round($price2 + $activitiesPrices + $guidesPrices);

        $data['price1'] = $this->generalSetting->getCustomPrice($currencyCode, $data['price1']);
        $data['price2'] = $this->generalSetting->getCustomPrice($currencyCode, $data['price2']);
        return $data;
    }

    private function validateActivityDate($cityStartDate, $cityEndDate, $activityStartDate, $activityNights = 0)
    {
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

    private function getServicePrice($serviceId, $serviceTypeName, $startDate, $noNights, $currencyCode, $selectedOptions = array(), $selectedExtras = array(), $cityOrderNumber = 0)
    {

        $servicePrice = 0;
        if (!empty($serviceId)) {
            $apiResponse = $this->getServicePriceFromTSAPI($serviceId, $serviceTypeName, $startDate, $noNights, $currencyCode);
            $servicePrice = $this->getPriceForSeletedOptionsAndExtras($apiResponse, $selectedOptions, $selectedExtras);
            $this->setLoggedError($cityOrderNumber, $serviceId, $serviceTypeName);
        }

        return $servicePrice;
    }

    private function getPriceForSeletedOptionsAndExtras($apiResponse, $selectedOptions, $selectedExtras)
    {
        $servicePrice = 0;
        /* echo "<br>apiResponse ";
          print_r($apiResponse);
          echo "<br>selectedOption ";
          print_r($selectedOptions);
          echo "------"; */
        if (!empty($apiResponse['serviceOptions'])) {
            foreach ($apiResponse['serviceOptions'] as $serviceOptionKey => $serviceOption) {

                foreach ($selectedOptions as $selectedOptionKey => $selectedOption) {
                    if (array_has($selectedOption, 'OptionID') && array_has($serviceOption, 'OptionID')) {
                        if ($selectedOption['OptionID'] == $serviceOption['OptionID']) {
                            $servicePrice += $serviceOption['TotalSellingPrice'];
                            # delete match one  for performance 
                            unset($selectedOptions[$selectedOptionKey]);
                            break;
                        }
                    }
                }
            }
        }

        if (!empty($apiResponse['serviceExtras'])) {
            foreach ($apiResponse['serviceExtras'] as $serviceExtraKey => $serviceExtra) {
                if (is_array($selectedExtras)) {
                    foreach ($selectedExtras as $selectedExtraKey => $selectedExtra) {
                        if ($selectedExtra['ServiceExtraId'] == $serviceExtra['ServiceExtraId']) {
                            $servicePrice += $selectedExtra['TOTALPRICE'];
                            # delete match one  for performance 
                            unset($selectedExtras[$selectedExtraKey]);
                            break;
                        }
                    }
                }
            }
        }
//        echo "<br> servicePrice $servicePrice";

        return $servicePrice;
    }

    function calculateOptionPricesForItineraryWithoutApiCall($itinerary)
    {
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

    function calculateTransferDetailsPriceWithOptions($city, $option, $data, $cityStartDate, $cityNights, $currencyCode, $cityKey, $occupancyId, $defaultQuantity, $adult, $child, $price)
    {
        if (!empty($city[$option]['arrivalDetail'])) {
            $this->travelStudioClient->setRoomDetails($occupancyId, $defaultQuantity, $adult + $child, $child);
            $services = $city[$option]['arrivalDetail'];
            foreach ($services as $serviceKey => $service) {
                if (isset($service['arrivalDetailsService'])) {
                    $selectedserviceExtras = array_get($service, 'selectedserviceExtras', []);
                    $optionsList = array(array($service['optionForService']), $selectedserviceExtras);
                    $data['city'][$cityKey][$option]['arrivalDetail'][$serviceKey]['servicePrice'] = $this->buildPramForGetServicePrice(
                            $service['arrivalDetailsService']['service_tsid'], 'service', $cityStartDate, $cityNights, $currencyCode, $optionsList, $cityKey
                    );
                    $price += $data['city'][$cityKey][$option]['arrivalDetail'][$serviceKey]['servicePrice'];
                }
            }
        }
        return ['city' => $city, 'data' => $data, 'price' => $price];
    }

    function calculateHotelPriceWithOptions($city, $option, $data, $cityStartDate, $cityNights, $currencyCode, $cityKey, $price)
    {
//        dump($city[$option]['hotel']['selectedhotelExtras']);
        if (!empty($city[$option]['hotel']) && !empty($city[$option]['hotel']['selectedhotelOptions'])) {
            $selectedhotelOptions = $city[$option]['hotel']['selectedhotelOptions'];
            $selectedhotelExtras = $city[$option]['hotel']['selectedhotelExtras'];
            $service = $city[$option]['hotel'];
            $this->buildPramForGetServicePrice($service['hotel_tsid'], 'hotel', $cityStartDate, $cityNights, $currencyCode, [0 => $selectedhotelOptions, 1 => $selectedhotelExtras], $cityKey);
            $selected = $this->getPricesForSelectedOptions($selectedhotelOptions, $selectedhotelExtras, $service['hotel_tsid'], $cityStartDate, $cityNights, $currencyCode);
            $city[$option]['hotel']['selectedhotelOptions'] = $selected['selectedhotelOptions'];
            $city[$option]['hotel']['selectedhotelExtras'] = $selected['selectedhotelExtras'];
            $data['city'][$cityKey][$option]['hotel']['selectedHotelPrice'] = $selected['selectedHotelPrice'];
            $price += $data['city'][$cityKey][$option]['hotel']['selectedHotelPrice'];
        } else if (!empty($city[$option]['hotel']['selectedHotelPrice'])) {
            $price += $city[$option]['hotel']['selectedHotelPrice'];
        }
        return ['city' => $city, 'data' => $data, 'price' => $price];
    }

    public function getPricesForSelectedOptions($selectedhotelOptions, $selectedhotelExtras, $serviceId, $dateOnWhichServiceIsRequired, $nightsForWhichServiceIsRequired, $currencyCode)
    {
        $travelStudio = app('TravelStudio');
        $selectedHotelPrice = 0;
        $dateOnWhichServiceIsRequired = $this->dateHelper->getMySqlDateFromNormalDate($dateOnWhichServiceIsRequired);
        $endDate = $this->dateHelper->addDaysToDate($dateOnWhichServiceIsRequired, $nightsForWhichServiceIsRequired);
        foreach ($selectedhotelOptions as $key => $selectedhotelOption) {
            $numberOfPassengers = $selectedhotelOption['adultCount'] + $selectedhotelOption['childCount'];
            $travelStudio->setRoomDetails($selectedhotelOption['Occupancy'], $selectedhotelOption['quantity'], $numberOfPassengers, $selectedhotelOption['childCount']);
            $travelStudio->getServicesPricesAndAvailability($serviceId, 2, $dateOnWhichServiceIsRequired, $nightsForWhichServiceIsRequired, $currencyCode, true);
            foreach ($travelStudio->serviceOptions as $serviceOption) {
                if ($selectedhotelOption['OptionID'] == $serviceOption['OptionID']) {
                    $selectedhotelOptions[$key]['TotalSellingPrice'] = $serviceOption['TotalSellingPrice'];
                    $selectedHotelPrice+=$selectedhotelOptions[$key]['TotalSellingPrice'];
                }
            }
        }
        $selectedExtraPrice = 0;
        foreach ($selectedhotelExtras as $iey => $selectedhotelExtra) {
            $extras = $travelStudio->getExtrasForAService(2, $serviceId, $dateOnWhichServiceIsRequired, $endDate, $currencyCode);
            foreach ($extras as $extra) {
                if ($extra['ServiceExtraId'] == $selectedhotelExtra['ServiceExtraId']) {
                    $selectedExtraPrice = $extra['TOTALPRICE'] * $selectedhotelExtra['quantity'];
                    $selectedhotelExtras[$iey]['TOTALPRICE'] = $selectedExtraPrice;
                    $selectedHotelPrice+=$selectedExtraPrice;
                }
            }
        }
        $return['selectedHotelPrice'] = $selectedHotelPrice;
        $return['selectedhotelOptions'] = $selectedhotelOptions;
        $return['selectedhotelExtras'] = $selectedhotelExtras;
        return $return;
    }

    function getPricesForTourManager($otherDetails, $data, $price, $currencyCode)
    {
        $return = [];
        if (!empty($otherDetails['tourManager'])) {
            foreach ($otherDetails['tourManager'] as $serviceKey => $service) {
                $optionsList = array();
                $optionsList[0] = (!empty($service['tourOptions']['selectedServiceOptions'])) ? $service['tourOptions']['selectedServiceOptions'] : '';
                $optionsList[1] = (!empty($service['tourOptions']['selectedServiceExtra'])) ? $service['tourOptions']['selectedServiceExtra'] : '';
                $otherDetails['tourManager'][$serviceKey]['price'] = $this->buildPramForGetServicePrice(
                        $service['tourServiceOptions']['service_tsid'], 'service', $service['startDate'], $service['nights'], $currencyCode, $optionsList
                );
                $data['tourManager'][$serviceKey]['price'] = $otherDetails['tourManager'][$serviceKey]['price'];
                $price += $otherDetails['tourManager'][$serviceKey]['price'];
            }
        }
        $return['price'] = $price;
        $return['data'] = $data;
        return $return;
    }

    private function getServicePriceFromTSAPI($serviceId, $serviceTypeName, $dateOnWhichServiceIsRequired = null, $nightsForWhichServiceIsRequired = null, $currencyCode = null)
    {
        $this->priceResponseErrorMessage = '';
        $this->priceResponseWarnMessage = '';
        $result = [];
        $result['serviceOptions'] = [];
        $result['serviceExtras'] = [];
        $result['priceOfService'] = 0;

        $limitToOneRoomType = true;
        if ($serviceTypeName == 'hotel') {
            $limitToOneRoomType = true;
            $this->travelStudioClient->setRoomDetails(2, 1, 1, 0);
        }
        $dateOnWhichServiceIsRequired = $this->dateHelper->getMySqlDateFromNormalDate($dateOnWhichServiceIsRequired);
        $this->travelStudioClient->getServicePriceAndAvailability($serviceId, $serviceTypeName, $dateOnWhichServiceIsRequired, $nightsForWhichServiceIsRequired, $currencyCode, $limitToOneRoomType);
        if (!$this->travelStudioClient->hasError()) {
            $result['serviceOptions'] = $this->travelStudioClient->serviceOptions;
            $endDate = $this->dateHelper->addDaysToDate($dateOnWhichServiceIsRequired, $nightsForWhichServiceIsRequired);
            $result['serviceExtras'] = $this->travelStudioClient->getExtrasForAService($this->travelStudioClient->getServiceTypeId(), $serviceId, $dateOnWhichServiceIsRequired, $endDate);
            $result['priceOfService'] = $this->travelStudioClient->getSservicePrice();
        }
        $this->setResponseErrorAndWarning();
        return $result;
    }

    function setResponseErrorAndWarning($travelStudioClient = null)
    {
        if (!$travelStudioClient) {
            $travelStudioClient = $this->travelStudioClient;
        }
        $this->priceResponseWarnMessage = null;
        $this->priceResponseErrorMessage = null;
        if ($travelStudioClient->getErrorType() == 'warning' || $travelStudioClient->getErrorType() == 'region_warning') {
            $this->priceResponseWarnMessage = $this->travelStudioClient->getErrorMsgs();
        }
        if ($travelStudioClient->getErrorType() == 'error') {
            $this->priceResponseErrorMessage = $travelStudioClient->getErrorMsgs();
        }
    }

    function setLoggedError($cityId, $serviceId, $serviceType)
    {
        if (!empty($this->priceResponseErrorMessage) && $serviceId > 0) {
            $this->priceResponseError[$cityId][$serviceType][$serviceId][] = $this->priceResponseErrorMessage;
        }
        if (!empty($this->priceResponseWarnMessage) && $serviceId > 0) {
            $this->priceResponseWarn[$cityId][$serviceType][$serviceId][] = $this->priceResponseWarnMessage;
        }
    }

    function getLoggedError()
    {
        return $this->priceResponseError;
    }

    function getLoggedwarning()
    {
        return $this->priceResponseWarn;
    }

}
