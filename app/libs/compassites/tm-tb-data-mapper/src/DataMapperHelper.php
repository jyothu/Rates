<?php

namespace Compassites\DataMapperHelper;

use Compassites\DateHelper\DateHelper;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of DataMapperHelper
 *
 * @author jeevan
 */
class DataMapperHelper
{

    protected $servicesAtItineraryLevel;
    protected $travelStudio;
    protected $dateHelper;

    public function __construct(DateHelper $dateHelper, \Service $service, \Illuminate\Database\Eloquent\Collection $collection, \Compassites\TravelStudioClient\TravelStudioClient $travelStudio)
    {
        $this->dateHelper = $dateHelper;
        $this->service = $service;
        $this->servicesAtItineraryLevel = $collection;
        $this->travelStudio = $travelStudio;
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
                'v3_itinerary_id' => $itinerary->v3_itinerary_id,
                'ts_booking_id' => $itinerary->ts_booking_id,
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
                    $service_id[] = $itinararyService->service->service_tsid;
                    $service[] = $itinararyService->service->service_name;
                    $service_price[] = $itinararyService->service_price;
                    $service_total_price+=$itinararyService->service_price;
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
                    $service2_id[] = $itinararyService->service->service_tsid;
                    $service2[] = $itinararyService->service->service_name;
                    $service2_price[] = $itinararyService->service_price;
                    $service2_total_price+=$itinararyService->service_price;
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

    function prepareItineraryDataFromItineraryToSendToTM($itinerary, $tmb3ItineraryId = null)
    {
        $itinerary->load('internalServices', 'cities');
        foreach ($itinerary->cities as $city) {
            $city->load('activities');
        }
        $option1_price = $itinerary->option1_price;
        $option2_price = $itinerary->option2_price;
        $itinerary->option1_price = $itinerary->price1_for2_people;
        $itinerary->option2_price = $itinerary->price2_for2_people;
        if ($tmb3ItineraryId > 0) {
//            $itinerary->itinerary_id = $tmb3ItineraryId;
        }
        $dataToSend['itinerary'] = $itinerary->toJson();
        $dataToSend['tmpostid'] = $itinerary->tmpostid;
        $itinerary->option1_price = $option1_price;
        $itinerary->option2_price = $option2_price;
        return $dataToSend;
    }

    function prepareItineraryDataFrom3For4($itineraryDataArray)
    {
        $itinerary = $this->saveItineraryDataFrom3To4($itineraryDataArray);
        $this->saveInternalServicesDataFrom3To4($itineraryDataArray, $itinerary);
        $this->saveCityDataFrom3To4($itineraryDataArray, $itinerary);
        $this->saveItineraryLevelServices($itinerary);
        return $itinerary;
    }

    function saveItineraryLevelServices($itinerary)
    {
        if ($this->servicesAtItineraryLevel->count() > 0) {
            $this->servicesAtItineraryLevel->each(function ($tineraryService) use ($itinerary) {
                $tineraryService->itinerary_city_id = 0;
                $tineraryService->itinerary_id = $itinerary->itinerary_id;
                $tineraryService->option_type = 0;
                $tineraryService->save();
                $this->saveServcieOptions($tineraryService->service->service_id, $tineraryService->service->service_type, $tineraryService->service->service_tsid, $tineraryService->from_date, 1, $itinerary->currency, ['itinerary_service_id' => $tineraryService->itinerary_service_id]);
            });
        }
    }

    function saveCityActivityDataFrom3To4($itineraryCityDataArray, $itineraryCity)
    {
        $field3to4MappingArray = [];
        $defaultFieldValueArray = ['itinerary_activity_id' => null, "itinerary_city_id" => $itineraryCity->itinerary_city_id];
        foreach ($itineraryCityDataArray['activities'] as $dataArray) {
            $modelObject = $this->createObjectFromDataAndTableName("itinerary_activity", $dataArray, $field3to4MappingArray, $defaultFieldValueArray);
            $modelObject->save();
            $this->saveServcieOptions($modelObject->activity->activity_id, $modelObject->activity->service_type, $modelObject->activity->activity_tsid, $modelObject->from_date, 1, $itineraryCity->itinerary->currency, ['itinerary_activity_id' => $modelObject->itinerary_activity_id]);
        }
    }

    function saveCityServiceDataFrom3To4($itineraryCityDataArray, $itineraryCity)
    {
        $field3to4MappingArray = [];
        $itineraryCityDataArrayFiltered['option1']['services'] = [];
        $itineraryCityDataArrayFiltered['option2']['services'] = [];

        $defaultFieldValueArray = ['itinerary_service_id' => null, "itinerary_city_id" => $itineraryCity->itinerary_city_id];
        foreach ($itineraryCityDataArray['option1']['services'] as $dataArray1) {
            foreach ($itineraryCityDataArray['option2']['services'] as $k => $dataArray2) {
                if ($dataArray2['service']['service_type'] == 4 && $dataArray1['service_id'] == $dataArray2['service_id']) {
                    unset($itineraryCityDataArray['option2']['services'][$k]);
                }
            }
        }
        if (!(count($itineraryCityDataArray['option2']['services']) > 0) && (count($itineraryCityDataArray['option1']['services']) > 0)) {
            $itineraryCityDataArray['option2']['services'] = $itineraryCityDataArray['option1']['services'];
            foreach ($itineraryCityDataArray['option2']['services'] as $i => $serv) {
                $itineraryCityDataArray['option2']['services'][$i]['option_type'] = 2;
            }
        }
        foreach ($itineraryCityDataArray['option1']['services'] as $dataArray1) {
            foreach ($itineraryCityDataArray['option2']['services'] as $k => $dataArray2) {
                if ($dataArray2['service']['service_type'] == 4 && $dataArray1['service_id'] == $dataArray2['service_id']) {
                    unset($itineraryCityDataArray['option2']['services'][$k]);
                }
            }
        }
        foreach (array('option1', 'option2') as $option) {
            foreach ($itineraryCityDataArray[$option]['services'] as $dataArray) {
                $v4ServcieObj = $this->service->where("service_tsid", "=", $dataArray['service']['service_tsid'])->first();
                if ($v4ServcieObj) {
                    $dataArray['service']['service_id'] = $v4ServcieObj->service_id;
                    $dataArray['service_id'] = $v4ServcieObj->service_id;
                } else {
                    break;
                }
                $modelObject = $this->createObjectFromDataAndTableName("itinerary_service", $dataArray, $field3to4MappingArray, $defaultFieldValueArray, 'ItinararyService');
                $modelObject->from_date;
                if ($modelObject->from_date == '0000-00-00') {
                    $modelObject->from_date = $itineraryCity->from_date;
                    $modelObject->to_date = $itineraryCity->to_date;
                }
                $modelObject->nights = $this->dateHelper->dateDifferenceInDays($modelObject->to_date, $itineraryCity->from_date);
                if (!((int) $modelObject->nights > 0)) {
                    $modelObject->nights = 1;
                    $modelObject->to_date = $this->dateHelper->addDaysToDate($modelObject->from_date, 1);
                }
                if ($this->isItineraryLevelService($modelObject)) {
                    $this->servicesAtItineraryLevel->add($modelObject);
                } else {
                    $modelObject->save();
                    if ($modelObject && $modelObject->service) {
                        $hotelOptionNumber = $option == 'option1' ? 1 : 2;
                        $modelObject->load('service');
                        $this->saveServcieOptions($modelObject->service->service_id, $modelObject->service->service_type, $modelObject->service->service_tsid, $itineraryCity->from_date, 1, $itineraryCity->itinerary->currency, ['option_type' => $hotelOptionNumber, 'itinerary_service_id' => $modelObject->itinerary_service_id]);
                    }
                }
            }
        }
    }

    function isItineraryLevelService($itinararyService)
    {
        $isItineraryLevelService = false;
        if ($itinararyService->service_id > 0) {
            $service_id = $itinararyService->service_id;
            $service = \Service::where('service_id', '=', $service_id)->first();
            if ($service) {
                $region = \Region::where('region_tsid', '=', $service->region_id)->first();
                $isItineraryLevelService = $region->region_parent_id > 0 ? false : true;
            }
        }
        return $isItineraryLevelService;
    }

    function saveCityDataFrom3To4($itineraryDataArray, $itinerary)
    {
        $field3to4MappingArray = [];
        $defaultFieldValueArray = ['itinerary_city_id' => null, "itinerary_id" => $itinerary->itinerary_id];
        foreach ($itineraryDataArray['cities'] as $dataArray) {
            if ($dataArray['hotel1_id'] > 0 && !($dataArray['hotel2_id'] > 0)) {
                $dataArray['hotel2_id'] = $dataArray['hotel1_id'];
                $dataArray['hotel2_price'] = $dataArray['hotel1_price'];
            }
            $region_tsid = array_get($dataArray, 'region.region_tsid', null);
            if ($region_tsid > 0) {
                $region = \Region::where('region_tsid', '=', $region_tsid)->first();
                $dataArray['region_id'] = $region ? $region->region_id : 0;
            }
            $modelObject = $this->createObjectFromDataAndTableName("itinerary_city", $dataArray, $field3to4MappingArray, $defaultFieldValueArray);
            $modelObject->save();
            $this->saveCityActivityDataFrom3To4($dataArray, $modelObject);
            $this->saveCityServiceDataFrom3To4($dataArray, $modelObject);
            foreach (['1', '2'] as $hotelOptionNumber) {
                $hotelId = "hotel{$hotelOptionNumber}_id";
                $hotelAttr = "hotel{$hotelOptionNumber}";
                if ($modelObject->$hotelId > 0) {
                    $hotelObj = $modelObject->$hotelAttr;
                    if ($hotelObj && $hotelObj->hotel_id > 0) {
                        $this->saveServcieOptions($modelObject->$hotelId, 2, $hotelObj->hotel_tsid, $modelObject->from_date, 1, $itinerary->currency, ['option_type' => $hotelOptionNumber, 'itinerary_city_id' => $modelObject->itinerary_city_id]);
                    }
                }
            }
        }
    }

    function saveInternalServicesDataFrom3To4($itineraryDataArray, $itinerary)
    {
        $field3to4MappingArray = [];
        $defaultFieldValueArray = ['itinerary_internal_service_id' => null, "itinerary_id" => $itinerary->itinerary_id];
        foreach ($itineraryDataArray['internal_services'] as $dataArray) {
            $modelObject = $this->createObjectFromDataAndTableName("itinerary_internal_service", $dataArray, $field3to4MappingArray, $defaultFieldValueArray, 'ItenararyInternalService');
            if ($modelObject->start_date == '0000-00-00' || !$modelObject->start_date) {
                $modelObject->start_date = $this->dateHelper->removeTimeFromMysqlDateTime($itinerary->start_date);
                $modelObject->end_date = $this->dateHelper->addDaysToDate($modelObject->start_date, 1);
            }
            $modelObject->nights = $this->dateHelper->dateDifferenceInDays($modelObject->end_date, $modelObject->start_date);
            if (!$modelObject->adult_count || $modelObject->adult_count == 0) {
                $modelObject->adult_count = 2;
                $modelObject->child_count = 0;
            }
            $modelObject->save();
            $this->saveServcieOptions($modelObject->internalService->service_id, 20, $modelObject->internalService->service_tsid, $modelObject->start_date, 1, $itinerary->currency, ['itinerary_internal_service_id' => $modelObject->itinerary_internal_service_id]);
        }
    }

    function saveItineraryDataFrom3To4($itineraryDataArray)
    {
        $field3to4MappingArray = ['adjustment_remark' => 'remarks'];
        $defaultFieldValueArray = ['adult' => 2, "itinerary_id" => null];
        $itinerary = $this->createObjectFromDataAndTableName("itinerary", $itineraryDataArray, $field3to4MappingArray, $defaultFieldValueArray);
        $itinerary->v3_itinerary_id = $itineraryDataArray['itinerary_id'];
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

    function removeAllItinerarRelatedServices($itinerary)
    {
        foreach ($itinerary->cities as $city) {
            $city->delete();
        }
        foreach ($itinerary->itinararyServices as $itinararyServices) {
            $itinararyServices->delete();
        }
        foreach ($itinerary->internalServices as $internalServices) {
            $internalServices->delete();
        }
    }

    function saveServcieOptions($serviceId, $serviceTypeId, $serviceTsid, $dateOnWhichServiceIsRequired, $nightsForWhichServiceIsRequired, $currencyCode, $relationArray = [])
    {
        $limitToOneRoomType = false;
        if ($serviceTypeId == 2) {
            $limitToOneRoomType = true;
        }
        $this->travelStudio->getServicesPricesAndAvailability($serviceTsid, $serviceTypeId, $dateOnWhichServiceIsRequired, $nightsForWhichServiceIsRequired, $currencyCode, $limitToOneRoomType);

        $defaultServiceOption = $this->travelStudio->defaultServiceOption;
        if (count($defaultServiceOption) > 0) {
            $mappedDbFields = array(
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
            foreach ($mappedDbFields as $mappedDbField => $responseKey) {
                if (array_key_exists($responseKey, $defaultServiceOption)) {
                    $mappedDbFields[$mappedDbField] = $defaultServiceOption[$responseKey];
                }
            }
            $defaultFieldValueArray = ['quantity' => 1, 'adult_count' => 2, "service_id" => $serviceId, "service_type_id" => $serviceTypeId, "service_tsid" => $serviceTsid];
            if (count($relationArray) > 0) {
                $defaultFieldValueArray = array_merge($defaultFieldValueArray, $relationArray);
            }
            $serviceOption = $this->createObjectFromDataAndTableName("service_options", $mappedDbFields, null, $defaultFieldValueArray, "ServiceOption");
            $serviceOption->save();
        }
    }

}
