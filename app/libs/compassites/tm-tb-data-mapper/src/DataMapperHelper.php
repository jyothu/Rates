<?php

namespace Compassites\DataMapperHelper;

use Compassites\DateHelper\DateHelper;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CurlHelper
 *
 * @author jeevan
 */
class DataMapperHelper
{

    public function __construct(DateHelper $dateHelper)
    {
        $this->dateHelper = $dateHelper;
    }

    function getSessionDataArrayFromItineraryObject($itinerary)
    {
        $sessionData = array();
        if ($itinerary) {
            $totalNumberOfNightsUsed = 0;
            $sessionData = array('itinerary', 'city');
            $start_date = $this->dateHelper->getNormalDateFromMySqlDate($itinerary->start_date);
            $end_date = $this->dateHelper->getNormalDateFromMySqlDate($itinerary->end_date);
            $internalserviceName = array();
            $internalservice_id = array();
            $internalservice_price_list = array();
            $internalservice_price = 0;
            foreach ($itinerary->internalServices as $internalService) {
                $internalservice_id[] = $internalService->internalService->service_tsid;
                $internalserviceName[] = $internalService->internalService->service_name;
                $internalservice_price_list[] = $internalService->internal_service_price;
                $internalservice_price+=$internalService->internal_service_price;
            }
            $previous_end_date = $this->dateHelper->getNormalDateFromMySqlDate($itinerary->cities[0]->to_date);
            $sessionData['itinerary'] = array(
                'current_start_date_for_accomodation' => '',
                'start-date' => $start_date,
                'end-date' => $end_date,
                'nights' => $itinerary->number_of_nights,
                'currency' => $itinerary->currency,
                'previous-end-date' => $previous_end_date,
                'internalservice' => implode("|", $internalserviceName),
                'internalservice_id' => implode("|", $internalservice_id),
                'internalservice_price_list' => implode("|", $internalservice_price_list),
                'internalservice_price' => $internalservice_price,
                'adjustment1' => $itinerary->adjustment1,
                'adjustment2' => $itinerary->adjustment2,
                'adjustment_remark' => $itinerary->adjustment_remark
            );

            foreach ($itinerary->cities as $key => $city) {
                $service_total_price = 0;
                $service = array();
                $service_id = array();
                $service_price = array();
                $from_date = $this->dateHelper->getNormalDateFromMySqlDate($city->from_date);
                $to_date = $this->dateHelper->getNormalDateFromMySqlDate($city->to_date);
                $option1 = $city->getOption1Attribute();
                $hotel1Obj = $option1['hotel'];
                $hotel1 = "";
                $hotel1_id = "";
                $hotel1_price = "";
                $totalNumberOfNightsUsed+=$city->number_of_nights;
                if ($hotel1Obj) {
                    $hotel1 = $hotel1Obj->hotel_name;
                    $hotel1_id = $hotel1Obj->hotel_tsid;
                    $hotel1_price = $city->hotel1_price;
                    //$hotel1_price = $hotel1Obj->hotel_price;
                }
                foreach ($option1['services'] as $itinararyService) {

                    $serviceArray = (array) $itinararyService->service;
                    if (!empty($serviceArray)) {
                        $service_id[] = $itinararyService->service->service_tsid;
                        $service[] = $itinararyService->service->service_name;
                        $service_price[] = $itinararyService->service_price;
                        $service_total_price+=$itinararyService->service_price;
                    }
                }

                $service2_total_price = 0;
                $service2 = array();
                $service2_id = array();
                $service2_price = array();
                $option2 = $city->getOption2Attribute();
                $hotel2Obj = $option2['hotel'];
                $hotel2 = "";
                $hotel2_id = "";
                $hotel2_price = "";
                if ($hotel2Obj) {
                    $hotel2 = $hotel2Obj->hotel_name;
                    $hotel2_id = $hotel2Obj->hotel_tsid;
                    $hotel2_price = $city->hotel2_price;
                    //$hotel2_price = $hotel2Obj->hotel_price;
                }
                foreach ($option2['services'] as $itinararyService) {
                    $serviceArray = (array) $itinararyService->service;
                    if (!empty($serviceArray)) {
                        $service2_id[] = $itinararyService->service->service_tsid;
                        $service2[] = $itinararyService->service->service_name;
                        $service2_price[] = $itinararyService->service_price;
                        $service2_total_price+=$itinararyService->service_price;
                    }
                }
                $sessionData['city'][$key]['city'] = array(
                    'nights' => $city->number_of_nights,
                    'name' => $city->region->region_name,
                    'id' => $city->region->region_tsid,
                    'service' => implode("|", $service),
                    'service_id' => implode("|", $service_id),
                    'service_price' => implode("|", $service_price),
                    'service_total_price' => $service_total_price,
                    'hotel' => $hotel1,
                    'hotel_id' => $hotel1_id,
                    'hotel_price' => $hotel1_price,
                    'service2' => implode("|", $service2),
                    'service2_id' => implode("|", $service2_id),
                    'service2_price' => implode("|", $service2_price),
                    'service2_total_price' => $service2_total_price,
                    'hotel2' => $hotel2,
                    'hotel2_id' => $hotel2_id,
                    'hotel2_price' => $hotel2_price,
                    'start-date' => $from_date,
                    'end-date' => $to_date
                );
                foreach ($city->activities as $activity) {

                    if ($activity->activity->serviceType->service_type_name == 'Activity') {

                        $sessionData['city'][$key]['activities']['activity'][] = $activity->activity->activity_name;
                        $sessionData['city'][$key]['activities']['activity_id'][] = $activity->activity->activity_tsid;
                        $sessionData['city'][$key]['activities']['price'][] = $activity->activity_price;
                        $sessionData['city'][$key]['activities']['from_date'][] = $this->dateHelper->getNormalDateFromMySqlDate($activity->from_date);
                        $sessionData['city'][$key]['activities']['nights'][] = $activity->nights;
                    } else {

                        $sessionData['city'][$key]['guides']['activity'][] = $activity->activity->activity_name;
                        $sessionData['city'][$key]['guides']['activity_id'][] = $activity->activity->activity_tsid;
                        $sessionData['city'][$key]['guides']['price'][] = $activity->activity_price;
                        $sessionData['city'][$key]['guides']['from_date'][] = $this->dateHelper->getNormalDateFromMySqlDate($activity->from_date);
                        $sessionData['city'][$key]['guides']['nights'][] = $activity->nights;
                    }
                }
            }
            $sessionData['price1'] = $itinerary->option1_price;
            $sessionData['price2'] = $itinerary->option2_price;
            $sessionData['internalServicePrice'] = $internalservice_price;
            $sessionData['itinerary_id'] = $itinerary->itinerary_id;
            $sessionData['totalNumberOfNightsUsed'] = $totalNumberOfNightsUsed;
        }
        return $sessionData;
    }

    function prepareItineraryDataFromItineraryToSendToTM($itinerary)
    {
        $itinerary->load('internalServices', 'cities');
        foreach ($itinerary->cities as $city) {
            $city->load('activities');
            $city->load('services');
        }
        $dataToSend['itinerary'] = $itinerary->toJson();
        $dataToSend['tmpostid'] = $itinerary->tmpostid;
        return $dataToSend;
    }

    function prepareItineraryDataFromItineraryToReservation($itinerary)
    {
        $itinerary->load('internalServices', 'cities', 'proposalReservations');
        foreach ($itinerary->cities as $city) {
            $city->load('activities');
            $city->load('services');
        }
        foreach ($itinerary->proposalReservations as $prop) {
            $prop->load('proposalReservationServices');
        }
        $dataToSend['itinerary'] = $itinerary->toJson();
        $dataToSend['tmpostid'] = $itinerary->tmpostid;
        return $dataToSend;
    }

    function prepareItineraryDataFrom4For3($itineraryDataArray)
    {
        $itinerary = $this->saveItineraryDataFrom4To3($itineraryDataArray);
        $this->saveInternalServicesDataFrom4To3($itineraryDataArray, $itinerary);
        $this->saveCityDataFrom4To3($itineraryDataArray, $itinerary);
        return $itinerary;
    }

    function saveCityActivityDataFrom4To3($itineraryCityDataArray, $itineraryCity)
    {
        $field3to4MappingArray = [];
        $defaultFieldValueArray = ['itinerary_activity_id' => null, "itinerary_city_id" => $itineraryCity->itinerary_city_id];
        foreach ($itineraryCityDataArray['activities'] as $dataArray) {
            $modelObject = $this->createObjectFromDataAndTableName("itinerary_activity", $dataArray, $field3to4MappingArray, $defaultFieldValueArray);
            $modelObject->save();
        }
    }

    function saveCityServiceDataFrom4To3($itineraryCityDataArray, $itineraryCity)
    {
        $field3to4MappingArray = [];
        $defaultFieldValueArray = ['itinerary_service_id' => null, "itinerary_city_id" => $itineraryCity->itinerary_city_id];
        foreach (array('option1', 'option2') as $option) {
            foreach ($itineraryCityDataArray[$option]['services'] as $dataArray) {
                $modelObject = $this->createObjectFromDataAndTableName("itinerary_service", $dataArray, $field3to4MappingArray, $defaultFieldValueArray, 'ItinararyService');
                $modelObject->save();
            }
        }
    }

    function saveCityDataFrom4To3($itineraryDataArray, $itinerary)
    {
        $field3to4MappingArray = [];
        $defaultFieldValueArray = ['itinerary_city_id' => null, "itinerary_id" => $itinerary->itinerary_id];
        foreach ($itineraryDataArray['cities'] as $dataArray) {
            $modelObject = $this->createObjectFromDataAndTableName("itinerary_city", $dataArray, $field3to4MappingArray, $defaultFieldValueArray);
            $modelObject->save();
            $this->saveCityActivityDataFrom4To3($dataArray, $modelObject);
            $this->saveCityServiceDataFrom4To3($dataArray, $modelObject);
        }
    }

    function saveInternalServicesDataFrom4To3($itineraryDataArray, $itinerary)
    {
        $field3to4MappingArray = [];
        $defaultFieldValueArray = ['itinerary_internal_service_id' => null, "itinerary_id" => $itinerary->itinerary_id];
        foreach ($itineraryDataArray['internal_services'] as $dataArray) {
            $modelObject = $this->createObjectFromDataAndTableName("itinerary_internal_service", $dataArray, $field3to4MappingArray, $defaultFieldValueArray, 'ItenararyInternalService');
            $modelObject->save();
        }
    }

    function saveItineraryDataFrom4To3($itineraryDataArray)
    {
        $field3to4MappingArray = ['remarks' => 'adjustment_remark'];
        $defaultFieldValueArray = ["itinerary_id" => null];
        $itinerary = $this->createObjectFromDataAndTableName("itinerary", $itineraryDataArray, $field3to4MappingArray, $defaultFieldValueArray);
        $itinerary->save();
        return $itinerary;
    }

    function createObjectFromDataAndTableName($tableName, $dataArray, $field3to4MappingArray = [], $defaultFieldValueArray = [], $modelName = null)
    {
        if (!$modelName) {
            $modelName = studly_case($tableName);
        }
        $modelNameWithPath = "\\" . $modelName;
        $modelObject = new $modelNameWithPath;

        foreach ($dataArray as $field => $value) {
            if (!is_array($value)) {
                if (\Schema::hasColumn($tableName, trim($field))) {
                    $modelObject->$field = trim($value);
                } elseif (array_key_exists($field, $field3to4MappingArray)) {
                    $field = $field3to4MappingArray[$field];
                    $modelObject->$field = trim($value);
                }
            }
        }
        foreach ($defaultFieldValueArray as $field => $value) {
            $modelObject->$field = trim($value);
        }
        return $modelObject;
    }

}
