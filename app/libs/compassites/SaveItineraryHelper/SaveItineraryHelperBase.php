<?php

/*
 * This file is part of TravelBuilder v0.4 project
 */

namespace Compassites\SaveItineraryHelper;

/**
 * It helps to save itinerary from 
 * session data which comprises of cities, hotels, arrival details, internal services,
 * car type, tour manager etc..
 * 
 * @package SaveItineraryHelperBase
 * @author Jeevan N <jeeeevz@gmail.com>
 * @version 1.0
 */
class SaveItineraryHelperBase
{

    /**
     * Get the session service object
     * @return \Illuminate\Session\Store
     */
    public function getSessionService()
    {
        return $this->session;
    }

    /**
     * Is there data in session that needs to be saved to itinerary table
     * @return Boolean
     */
    public function hasItineraryLevelData()
    {
        $sessionItineraryData = $this->getItinerarySessionData()['itinerary'];
        return ($sessionItineraryData['startdate'] && $sessionItineraryData['enddate']) ? true : false;
    }

    /**
     * Does the session hold data for itinerary
     * @return boolean
     */
    public function hasItinerarySession()
    {
        return $this->getSessionService()->has('itineraryData');
    }

    /**
     * Get the data stored in session with key ItineraryData having whole itinerary data
     * @return Array The array of data
     */
    public function getItinerarySessionData()
    {
        return $this->getSessionService()->get('itineraryData');
    }

    /**
     * Get data from session to be inserted to itinerary table
     * @return Array
     */
    public function getItineraryData()
    {
        return $this->getItinerarySessionData()['itinerary'];
    }

    /**
     * It maps between the database column names and session data array's keys
     * Session associate array keys seperated by dots
     * If the session data key is given null, it will take corresponding DB field name for processing session data
     * @return Array Array mapped with DB field name as key and session array key as value
     */
    public function mapSessionItineraryDataKeysAndDBFieldNames()
    {
        return array(
            'adult' => 'itinerary.adult',
            'child' => 'itinerary.child',
            'currency' => 'itinerary.currency',
            'start_date' => 'itinerary.startdate',
            'end_date' => 'itinerary.enddate',
            'number_of_nights' => 'itinerary.nights',
            'option1_price' => 'price1',
            'option2_price' => 'price2',
            'adjustment1' => 'itinerary.otherDetails.amnt_option1',
            'adjustment2' => 'itinerary.otherDetails.amnt_option2',
            'remarks' => 'itinerary.otherDetails.remarks',
            'ts_booking_id' => 'itinerary.ts_booking_id',
            'v3_itinerary_id' => 'itinerary.v3_itinerary_id',
            'itinerary_id' => 'itinerary.itinerary_id',
            
        );
    }

    /**
     * Create an array with DB field names as keys and session data as values
     * @return Array Data as an array with keys representing the database field names
     */
    public function getItineraryLevelDataForDB()
    {
        $itineraryLevelDataArray = array();
        $itineraryLevelSessionData = $this->getItinerarySessionData();
        $itineraryLevelSessionData = $this->priceCalculation->reCalculateItinerarayPrices($this->itinerary, $itineraryLevelSessionData);
        $itineraryLevelDBFieldNamesToSessionDataKeysMappingArray = $this->mapSessionItineraryDataKeysAndDBFieldNames();
        foreach ($itineraryLevelDBFieldNamesToSessionDataKeysMappingArray as $dbFieldName => $sessionKey) {
            $sessionKey = $sessionKey ? $sessionKey : $dbFieldName;
            $sessionValue = array_get($itineraryLevelSessionData, $sessionKey);
            $itineraryLevelDataArray[$dbFieldName] = $sessionValue;
        }
        $itineraryLevelDataArray['start_date'] = $this->dateHelper->getMySqlDateTimeFromNormalDate($itineraryLevelDataArray['start_date']);
        $itineraryLevelDataArray['end_date'] = $this->dateHelper->getMySqlDateTimeFromNormalDate($itineraryLevelDataArray['end_date']);
        return $itineraryLevelDataArray;
    }

    public function hasCityLevelData()
    {
        return count($this->getItinerarySessionData()['city']) > 0;
    }

    public function getCitySessionData()
    {
        return $this->getItinerarySessionData()['city'];
    }

    public function getCityLevelDataForDB()
    {
        $citiesLevelDataArray = array();
        $citiesLevelSessionData = $this->getCitySessionData();
        $cityLevelDBFieldNamesToSessionDataKeysMappingArray = $this->mapSessionCityDataKeysAndDBFieldNames();
        foreach ($citiesLevelSessionData as $cityLevelSessionData) {
            $city = $this->mapSessionArrayToDBFieldKeyAndValueArray($cityLevelSessionData, $cityLevelDBFieldNamesToSessionDataKeysMappingArray);
            $city['from_date'] = $this->dateHelper->getMySqlDateFromNormalDate($city['from_date']);
            $city['to_date'] = $this->dateHelper->getMySqlDateFromNormalDate($city['to_date']);
            $citiesLevelDataArray[] = array('db' => $city, 'session' => $cityLevelSessionData);
        }
        return $citiesLevelDataArray;
    }

    public function mapSessionCityDataKeysAndDBFieldNames()
    {
        return array(
            'itinerary_id' => null,
            'number_of_nights' => 'nightCount',
            'hotel1_price' => 'optionone.hotel.selectedHotelPrice',
            'hotel2_price' => 'optiontwo.hotel.selectedHotelPrice',
            'region_id' => null,
            'hotel1_id' => 'optionone.hotel.hotel_id',
            'hotel2_id' => 'optiontwo.hotel.hotel_id',
            'from_date' => 'start-date',
            'to_date' => 'end-date'
        );
    }

    public function hasHotelServiceOptions($citySessionData, $option)
    {
        return (count(array_get($citySessionData, "$option.hotel.selectedhotelOptions")) > 0);
    }

    public function getHotelServiceOptions($citySessionData, $option)
    {
        $servcieOptions = array_get($citySessionData, "$option.hotel.selectedhotelOptions");
        return is_array($servcieOptions) ? $servcieOptions : array();
    }

    public function mapSessionArrayToDBFieldKeyAndValueArray($sessionArray, $dbFieldNameToSessionKeyMapArray)
    {
        $dataArrayForDb = array();
        foreach ($dbFieldNameToSessionKeyMapArray as $dbFieldName => $sessionKey) {
            $sessionKey = $sessionKey ? $sessionKey : $dbFieldName;
            $sessionValue = array_get($sessionArray, $sessionKey);
            $dataArrayForDb[$dbFieldName] = $sessionValue;
        }
        return $dataArrayForDb;
    }

    public function mapSessionServiceOptionsDataKeysAndDBFieldNames()
    {
        return array(
            'service_id' => '',
            'service_type_id' => '',
            'service_tsid' => '',
            'option_type' => '',
            'option_id' => 'OptionID',
            'option_name' => 'ServiceOptionName',
            'quantity' => null,
            'adult_count' => 'adultCount',
            'child_count' => 'childCount',
            'option_price' => 'TotalSellingPrice',
            'occupancy' => 'Occupancy'
        );
    }

    public function mapSessionHotelServiceOptionsDataKeysAndDBFieldNames()
    {
        return $this->mapSessionServiceOptionsDataKeysAndDBFieldNames();
    }

    public function getHotelServiceOptionsLevelDataForDB($hotelOptionsSessionArray, $optionName, $itineraryCity)
    {
        $hotelOptionsLevelDBFieldNamesToSessionDataKeysMappingArray = $this->mapSessionHotelServiceOptionsDataKeysAndDBFieldNames();
        $hotelOptionsLevelDataForDBArray = $this->mapSessionArrayToDBFieldKeyAndValueArray($hotelOptionsSessionArray, $hotelOptionsLevelDBFieldNamesToSessionDataKeysMappingArray);
        if ($optionName == "optionone") {
            $hotel = $itineraryCity->hotel1;
            $option_type = 1;
        } else if ($optionName == "optiontwo") {
            $hotel = $itineraryCity->hotel2;
            $option_type = 2;
        }
        if(!($hotelOptionsLevelDataForDBArray['quantity'] > 0)){
            $hotelOptionsLevelDataForDBArray['quantity'] = 1;
        }
        $hotelOptionsLevelDataForDBArray['service_type_id'] = 2;
        $hotelOptionsLevelDataForDBArray['service_id'] = $hotel->hotel_id;
        $hotelOptionsLevelDataForDBArray['service_tsid'] = $hotel->hotel_tsid;
        $hotelOptionsLevelDataForDBArray['option_type'] = $option_type;
        $hotelOptionsLevelDataForDBArray['itinerary_city_id'] = $itineraryCity->itinerary_city_id;

        return $hotelOptionsLevelDataForDBArray;
    }

    public function saveHotelServiceOptionsLevelDataForDB($citySessionData, $itineraryCity)
    {
        foreach (array("optionone", "optiontwo") as $option) {
            $hotelOptions = array();
            if ($this->hasHotelServiceOptions($citySessionData, $option)) {
                $hotelOptionsSessionDataArray = $this->getHotelServiceOptions($citySessionData, $option);
                foreach ($hotelOptionsSessionDataArray as $hotelOptionSessionDataArray) {
                    $hotelOptionsLevelDataForDBArray = $this->getHotelServiceOptionsLevelDataForDB($hotelOptionSessionDataArray, $option, $itineraryCity);
                    $hotelOptions[] = $this->serviceOption->saveServcieOptionFromKeyValuePairArray($hotelOptionsLevelDataForDBArray);
                }
            }
        }
        return $hotelOptions;
    }

    public function hasTransferDetails($citySessionData, $option)
    {
        return (count(array_get($citySessionData, "$option.arrivalDetail")) > 0);
    }

    public function getTransferDetails($citySessionData, $option)
    {
        return array_get($citySessionData, "$option.arrivalDetail");
    }

    function getTransferDetailForDb($transferDetailFromSession, $itineraryCity, $option)
    {
        $dbFieldNameToSessionKeyMapArray = $this->mapSessionTranferDetailsAndDBFieldNames();
        $dataArrayForDb = $this->mapSessionArrayToDBFieldKeyAndValueArray($transferDetailFromSession, $dbFieldNameToSessionKeyMapArray);
        $option = $option == 'optionone' ? 1 : 2;
        $dataArrayForDb['option_type'] = $option;
        $dataArrayForDb['itinerary_city_id'] = $itineraryCity->itinerary_city_id;
        $dataArrayForDb['from_date'] = $itineraryCity->from_date;
        $dataArrayForDb['to_date'] = $itineraryCity->to_date;
        return $dataArrayForDb;
    }

    public function mapSessionTranferDetailsAndDBFieldNames()
    {
        return array(
            'service_id' => 'arrivalDetailsService.service_id',
            'service_price' => 'servicePrice',
            'option_type' => '',
            'itinerary_city_id' => '',
            'from_date' => '',
            'to_date' => ''
        );
    }

    public function saveTransferDetail($transferDetailFromSession, $itineraryCity, $option)
    {
        $transferDetailKeyValueArray = $this->getTransferDetailForDb($transferDetailFromSession, $itineraryCity, $option);
        return $this->itinararyService->saveCityServiceFromKeyValuePairArray($transferDetailKeyValueArray);
    }

    function hasTransferDetailOption($transferDetailFromSession)
    {
        return array_get($transferDetailFromSession, "optionForService.OptionID") > 0;
    }

    function getTransferDetailOption($transferDetailFromSession)
    {
        return array_get($transferDetailFromSession, "optionForService");
    }

    function getServiceOptionForDb($servcieOptionFromSession, $itineraryService)
    {
        $dbFieldNameToSessionKeyMapArray = $this->mapSessionServiceOptionsDataKeysAndDBFieldNames();
        $dataArrayForDb = $this->mapSessionArrayToDBFieldKeyAndValueArray($servcieOptionFromSession, $dbFieldNameToSessionKeyMapArray);
        $dataArrayForDb['service_type_id'] = $itineraryService->service->service_type;
        $dataArrayForDb['service_tsid'] = $itineraryService->service->service_tsid;
        $dataArrayForDb['service_id'] = $itineraryService->service->service_id;
        $dataArrayForDb['option_type'] = $itineraryService->option_type;
//        $dataArrayForDb['itinerary_city_id'] = $itineraryService->itinerary_city_id;
        $dataArrayForDb['itinerary_service_id'] = $itineraryService->itinerary_service_id;
        return $dataArrayForDb;
    }

    function saveTransferDetailOption($servcieOptionFromSession, $itineraryService)
    {
        $transferDetailOptionsLevelDataForDBArray = $this->getServiceOptionForDb($servcieOptionFromSession, $itineraryService);
        return $this->serviceOption->saveServcieOptionFromKeyValuePairArray($transferDetailOptionsLevelDataForDBArray);
    }

    public function mapSessionServiceExtrasDataKeysAndDBFieldNames()
    {
        return array(
            'service_id' => '',
            'service_type_id' => '',
            'service_tsid' => 'ServiceTypeTypeID',
            'extras_type' => '',
            'extras_id' => 'ServiceExtraId',
            'extras_name' => 'ServiceTypeExtraName',
            'quantity' => null,
            'extras_price' => 'TOTALPRICE',
            'option_type' => ''
        );
    }

    public function getServiceExtraForDb($servcieExtraFromSession, $itineraryService)
    {
        $dbFieldNameToSessionKeyMapArray = $this->mapSessionServiceExtrasDataKeysAndDBFieldNames();
        $dataArrayForDb = $this->mapSessionArrayToDBFieldKeyAndValueArray($servcieExtraFromSession, $dbFieldNameToSessionKeyMapArray);
        return $dataArrayForDb;
    }

    public function saveExtras($sessionServiceArray, $savedServiceObject)
    {                 
        $dbFieldNameToSessionKeyMapArray = $this->mapSessionServiceExtrasDataKeysAndDBFieldNames();
        foreach (array("internalService.selectedServiceExtra","carOptions.selectedServiceExtra","selectedserviceExtras","selectedServiceExtra", "optionone.hotel.selectedhotelExtras", "optiontwo.hotel.selectedhotelExtras","tourOptions.selectedServiceExtra") as $extraArrayKey) {
            if (count(array_get($sessionServiceArray, $extraArrayKey)) > 0) {
                $selectedExtras = array_get($sessionServiceArray,$extraArrayKey);
                foreach ($selectedExtras as $servcieExtraFromSession) {
                    $dataArrayForDb = $this->mapSessionArrayToDBFieldKeyAndValueArray($servcieExtraFromSession, $dbFieldNameToSessionKeyMapArray);
                    $savedServcieObjectClassName = get_class($savedServiceObject);
                    switch ($savedServcieObjectClassName) {
                        case "ItineraryActivity" :
                            $serviceObject = $savedServiceObject->activity;
                            $dataArrayForDb['service_type_id'] = $serviceObject->service_type;
                            $dataArrayForDb['service_tsid'] = $serviceObject->activity_tsid;
                            $dataArrayForDb['service_id'] = $serviceObject->activity_id;
                            $dataArrayForDb['option_type'] = 0;
                            $dataArrayForDb['itinerary_activity_id'] = $savedServiceObject->itinerary_activity_id;
                            $this->serviceExtra->saveServcieExtraFromKeyValuePairArray($dataArrayForDb);
                            break;
                        case "ItenararyInternalService" :
                            $serviceObject = $savedServiceObject->internalService;
                            $dataArrayForDb['service_type_id'] = $serviceObject->service_type;
                            $dataArrayForDb['service_tsid'] = $serviceObject->service_tsid;
                            $dataArrayForDb['service_id'] = $serviceObject->service_id;
                            $dataArrayForDb['option_type'] = 0;
                            $dataArrayForDb['itinerary_internal_service_id'] = $savedServiceObject->itinerary_internal_service_id;
                            $this->serviceExtra->saveServcieExtraFromKeyValuePairArray($dataArrayForDb);
                            break;
                        case "ItineraryCity" :
                            $hotelServiceObject = null;
                            if ($extraArrayKey == "optionone.hotel.selectedhotelExtras") {
                                $hotelServiceObject = $savedServiceObject->hotel1;
                                $option = 1;
                            } else {
                                $hotelServiceObject = $savedServiceObject->hotel2;
                                $option = 2;
                            }
                            if ($hotelServiceObject) {
                                $dataArrayForDb['service_type_id'] = 2;
                                $dataArrayForDb['service_tsid'] = $hotelServiceObject->hotel_tsid;
                                $dataArrayForDb['service_id'] = $hotelServiceObject->hotel_id;
                                $dataArrayForDb['option_type'] = $option;
                                $dataArrayForDb['extras_price'] *= $dataArrayForDb['quantity'];
                                $dataArrayForDb['itinerary_city_id'] = $savedServiceObject->itinerary_city_id;
                                $this->serviceExtra->saveServcieExtraFromKeyValuePairArray($dataArrayForDb);
                            }
                            break;
                        case "ItinararyService" :
                            $serviceObject = $savedServiceObject->service;
                            $dataArrayForDb['service_type_id'] = $serviceObject->service_type;
                            $dataArrayForDb['service_tsid'] = $serviceObject->service_tsid;
                            $dataArrayForDb['service_id'] = $serviceObject->service_id;
                            $dataArrayForDb['option_type'] = $savedServiceObject->option_type;
                            $dataArrayForDb['itinerary_service_id'] = $savedServiceObject->itinerary_service_id;
                            $this->serviceExtra->saveServcieExtraFromKeyValuePairArray($dataArrayForDb);
                            break;
                        default :
                            break;
                    }
                }
            }
        }
    }

}
