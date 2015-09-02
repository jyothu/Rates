<?php

/*
 * This file is part of TravelBuilder v0.4 project
 */

namespace Compassites\SaveItineraryHelper;

use Illuminate\Session\Store as Session;
use Compassites\DateHelper\DateHelper;

/**
 * It helps to save itinerary from 
 * session data which comprises of cities, hotels, arrival details, internal services,
 * car type, tour manager etc..
 * 
 * @package SaveItineraryHelper
 * @author Jeevan N <jeeeevz@gmail.com>
 * @version 1.0
 */
class SaveItineraryHelper extends SaveItineraryHelperBase
{

    /**
     * Holds Session service which can be used to access/modify session data
     * @var Session
     * @access protected
     */
    protected $session;
    protected $dateHelper;

    /**
     * Model object for itinerary
     * @var \Itinerary 
     */
    protected $itinerary;
    protected $itineraryCity;
    protected $serviceOption;
    protected $savedItinerary;
    protected $itinararyService;
    protected $itenararyInternalService;
    protected $priceCalculation;

    /**
     * Initialises services required for saving itinerary data
     * @param Store $session To hold session service object     
     */
    public function __construct(Session $session, DateHelper $dateHelper, \Itinerary $itinerary, \ItineraryCity $itineraryCity, \ServiceOption $serviceOption, \ItinararyService $itinararyService, \InternalService $internalService, \ItenararyInternalService $itenararyInternalService, \Service $service, \Activity $activity, \ItineraryActivity $itineraryActivity, \ServiceExtra $serviceExtra, \Compassites\DataMapperHelper\DataMapperHelper $dataMapperHelper, \Compassites\PriceCalculation\PriceCalculation $priceCalculation)
    {
        $this->session = $session;
        $this->itinerary = $itinerary;
        $this->itineraryCity = $itineraryCity;
        $this->dateHelper = $dateHelper;
        $this->serviceOption = $serviceOption;
        $this->itinararyService = $itinararyService;
        $this->internalService = $internalService;
        $this->itenararyInternalService = $itenararyInternalService;
        $this->service = $service;
        $this->activity = $activity;
        $this->itineraryActivity = $itineraryActivity;
        $this->serviceExtra = $serviceExtra;
        $this->dataMapperHelper = $dataMapperHelper;
        $this->priceCalculation = $priceCalculation;
    }

    /**
     * Save data in itinerary table from correponding data in session
     * @return \Itinerary
     */
    public function saveItineraryLevelData()
    {
        if ($this->hasItinerarySession() && $this->hasItineraryLevelData()) {
            $itineraryKeyValuePairArray = $this->getItineraryLevelDataForDB();
            $itinerary = null;
            if ($itineraryKeyValuePairArray['itinerary_id']) {
                $itinerary = $this->itinerary->find($itineraryKeyValuePairArray['itinerary_id']);
            }

            return $this->itinerary->saveItineraryFromKeyValuePairArray($itinerary, $itineraryKeyValuePairArray);
        }
    }

    /**
     * Save data in itinerary_city table from correponding data in session
     * @param \Itinerary $itinerary Itinerary to wchich the city belongs to
     * @return \ItineraryCity
     */
    public function saveCityLevelDataForDB($itinerary)
    {
        if ($this->hasItinerarySession() && $this->hasCityLevelData()) {
            $citiesKeyValuePairArray = $this->getCityLevelDataForDB();
            foreach ($citiesKeyValuePairArray as $cityKeyValuePairArray) {
                $itineraryCity = $this->itineraryCity->saveItineraryCityFromKeyValuePairArray($cityKeyValuePairArray['db'], $itinerary);
                $itineraryCities[] = $itineraryCity;
                $this->saveExtras($cityKeyValuePairArray['session'], $itineraryCity);
                $this->saveHotelServiceOptionsLevelDataForDB($cityKeyValuePairArray['session'], $itineraryCity);
                $this->saveTransferDetails($cityKeyValuePairArray['session'], $itineraryCity);
                $this->saveActivityAndGuide($cityKeyValuePairArray['session'], $itineraryCity);
            }
            return $itineraryCities;
        }
    }

    function saveTransferDetails($citySessionData, $itineraryCity)
    {
        $itinararyServices = array();
        foreach (array("optionone", "optiontwo") as $option) {
            if ($this->hasTransferDetails($citySessionData, $option)) {
                $transferDetailsSession = $this->getTransferDetails($citySessionData, $option);
                foreach ($transferDetailsSession as $transferDetailSession) {
//                    $serviceObj = $this->service->where('service_tsid', '=', $transferDetailSession['service_id'])->first();
//                    $transferDetailSession['service_id'] = $serviceObj->service_id;
                    $itinararyService = $this->saveTransferDetail($transferDetailSession, $itineraryCity, $option);
                    $this->saveExtras($transferDetailSession, $itinararyService);
                    $itinararyServices[] = $itinararyService;
                    if ($this->hasTransferDetailOption($transferDetailSession)) {
                        $transferDetailOptionFromSession = $this->getTransferDetailOption($transferDetailSession);
                        $this->saveTransferDetailOption($transferDetailOptionFromSession, $itinararyService);
                    }
                }
            }
        }
        return $itinararyServices;
    }

    public function mapSessionIternalServiceKeysAndDBFieldNames()
    {
        return array(
            'itinerary_id' => '',
            'internal_service_id' => 'internalServicesOptions.service_tsid',
            'internal_service_price' => 'price',
            'adult_count' => 'guests.adult',
            'child_count' => 'guests.child',
            'start_date' => 'startDate',
            'end_date' => '',
            'nights' => null
        );
    }

    function saveIternalServices($itinerary)
    {
        $itenararyInternalServices = array();
        if ($itinerary) {
            $itinerarySessionData = $this->getItinerarySessionData();
            $internalServices = [];
            if (array_key_exists('internalService', $itinerarySessionData['itinerary']['otherDetails'])) {
                $internalServices = $itinerarySessionData['itinerary']['otherDetails']['internalService'];
            }
            foreach ($internalServices as $internalService) {
                $dbFieldNameToSessionKeyMapArray = $this->mapSessionIternalServiceKeysAndDBFieldNames();
                $iternalServiceDataForDbAsArray = $this->mapSessionArrayToDBFieldKeyAndValueArray($internalService, $dbFieldNameToSessionKeyMapArray);
                $iternalServiceDataForDbAsArray['start_date'] = $this->dateHelper->getMySqlDateFromNormalDate($iternalServiceDataForDbAsArray['start_date']);
                $iternalServiceDataForDbAsArray['end_date'] = $this->dateHelper->addDaysToDate($iternalServiceDataForDbAsArray['start_date'], $iternalServiceDataForDbAsArray['nights']);
                $iternalServiceDataForDbAsArray['itinerary_id'] = $itinerary->itinerary_id; 
                $internalServiceObj = $this->internalService->where('service_tsid', '=', $iternalServiceDataForDbAsArray['internal_service_id'])->first();
                $iternalServiceDataForDbAsArray['internal_service_id'] = $internalServiceObj->service_id;
                $itenararyInternalService = $this->itenararyInternalService->saveItenararyInternalServiceFromKeyValuePairArray($iternalServiceDataForDbAsArray);
                $this->saveExtras($internalService, $itenararyInternalService);
                $itenararyInternalServices[] = $itenararyInternalService;
                $selectedServiceOptions = $internalService['internalService']['selectedServiceOptions'];
                foreach ($selectedServiceOptions as $selectedServiceOption) {
                    $dbFieldNameToSessionKeyMapArray = $this->mapSessionServiceOptionsDataKeysAndDBFieldNames();
                    $dataArrayForDb = $this->mapSessionArrayToDBFieldKeyAndValueArray($selectedServiceOption, $dbFieldNameToSessionKeyMapArray);
                    $dataArrayForDb['itinerary_internal_service_id'] = $itenararyInternalService->itinerary_internal_service_id;
                    $dataArrayForDb['service_id'] = $itenararyInternalService->internalService->service_id;
                    $dataArrayForDb['service_tsid'] = $itenararyInternalService->internalService->service_tsid;
                    $dataArrayForDb['service_type_id'] = $itenararyInternalService->internalService->service_type;
                    $this->serviceOption->saveServcieOptionFromKeyValuePairArray($dataArrayForDb);
                }
            }
        }
        return $itenararyInternalServices;
    }

    public function mapSessionItineraryServiceKeysAndDBFieldNames()
    {
        return array(
            'service_id' => '',
            'service_price' => 'price',
            'option_type' => '',
            'itinerary_city_id' => '',
            'from_date' => 'startDate',
            'to_date' => '',
            'nights' => null
        );
    }

    function saveCarTypeServices($itinerary)
    {
        if ($itinerary) {
            $itinerarySessionData = $this->getItinerarySessionData();
            if (array_key_exists('carService', $itinerarySessionData['itinerary']['otherDetails'])) {
                $carServices = $itinerarySessionData['itinerary']['otherDetails']['carService'];                
                $itinararyServices = [];
                foreach ($carServices as $i => $carService) {
                    $serviceObj = $this->service->where('service_tsid', '=', $carService['carserviceOptions']['service_tsid'])->first();                   
                    if ($serviceObj && $serviceObj->service_id > 0) {
                        $dbFieldNameToSessionKeyMapArray = $this->mapSessionItineraryServiceKeysAndDBFieldNames();
                        $carTypeServiceDataForDbAsArray = $this->mapSessionArrayToDBFieldKeyAndValueArray($carService, $dbFieldNameToSessionKeyMapArray);
                        $carTypeServiceDataForDbAsArray['from_date'] = $this->dateHelper->getMySqlDateFromNormalDate($carTypeServiceDataForDbAsArray['from_date']);
                        $nights = (int) $carTypeServiceDataForDbAsArray['nights'];
                        if (!($nights > 0)) {
                            $nights = 1;
                        }
                        $carTypeServiceDataForDbAsArray['nights'] = $nights;
                        $carTypeServiceDataForDbAsArray['to_date'] = $this->dateHelper->addDaysToDate($carTypeServiceDataForDbAsArray['from_date'], $carTypeServiceDataForDbAsArray['nights']);
                        $carTypeServiceDataForDbAsArray['itinerary_id'] = $itinerary->itinerary_id;
                        $carTypeServiceDataForDbAsArray['service_id'] = $serviceObj->service_id;
                        $itinararyService = $this->itinararyService->saveCityServiceFromKeyValuePairArray($carTypeServiceDataForDbAsArray);
                        $this->saveExtras($carService, $itinararyService);
                        $itinararyServices[] = $itinararyService;
                        $selectedServiceOptions = $carService['carOptions']['selectedServiceOptions'];
                        foreach ($selectedServiceOptions as $selectedServiceOption) {
                            $dbFieldNameToSessionKeyMapArray = $this->mapSessionServiceOptionsDataKeysAndDBFieldNames();
                            $dataArrayForDb = $this->mapSessionArrayToDBFieldKeyAndValueArray($selectedServiceOption, $dbFieldNameToSessionKeyMapArray);
                            $dataArrayForDb['itinerary_service_id'] = $itinararyService->itinerary_service_id;
                            $dataArrayForDb['service_id'] = $serviceObj->service_id;
                            $dataArrayForDb['service_tsid'] = $serviceObj->service_tsid;
                            $dataArrayForDb['service_type_id'] = $serviceObj->service_type;
                            $this->serviceOption->saveServcieOptionFromKeyValuePairArray($dataArrayForDb);
                        }                       
                    }                  
                }
                return $itinararyServices;                
            }
        }
    }

    function saveTourManager($itinerary)
    {
        if ($itinerary) {
            $itinerarySessionData = $this->getItinerarySessionData();
            if (array_key_exists('tourManager', $itinerarySessionData['itinerary']['otherDetails'])) {
                $tourManagers = $itinerarySessionData['itinerary']['otherDetails']['tourManager'];
                foreach ($tourManagers as $tourManager) {
                    $serviceObj = $this->service->where('service_tsid', '=', $tourManager['tourServiceOptions']['service_tsid'])->first();
                    $dbFieldNameToSessionKeyMapArray = $this->mapSessionItineraryServiceKeysAndDBFieldNames();
                    $tourManagerServiceDataForDbAsArray = $this->mapSessionArrayToDBFieldKeyAndValueArray($tourManager, $dbFieldNameToSessionKeyMapArray);
                    $tourManagerServiceDataForDbAsArray['from_date'] = $this->dateHelper->getMySqlDateFromNormalDate($tourManagerServiceDataForDbAsArray['from_date']);
                    $tourManagerServiceDataForDbAsArray['to_date'] = $this->dateHelper->addDaysToDate($tourManagerServiceDataForDbAsArray['from_date'], $tourManagerServiceDataForDbAsArray['nights']);
                    $tourManagerServiceDataForDbAsArray['itinerary_id'] = $itinerary->itinerary_id;
                    $tourManagerServiceDataForDbAsArray['service_id'] = $serviceObj->service_id;
                    $itinararyService = $this->itinararyService->saveCityServiceFromKeyValuePairArray($tourManagerServiceDataForDbAsArray);
                    $this->saveExtras($tourManager, $itinararyService);
                    $itinararyServices[] = $itinararyService;
                    $selectedServiceOptions = $tourManager['tourOptions']['selectedServiceOptions'];
                    foreach ($selectedServiceOptions as $selectedServiceOption) {
                        $dbFieldNameToSessionKeyMapArray = $this->mapSessionServiceOptionsDataKeysAndDBFieldNames();
                        $dataArrayForDb = $this->mapSessionArrayToDBFieldKeyAndValueArray($selectedServiceOption, $dbFieldNameToSessionKeyMapArray);
                        $dataArrayForDb['itinerary_service_id'] = $itinararyService->itinerary_service_id;
                        $dataArrayForDb['service_id'] = $serviceObj->service_id;
                        $dataArrayForDb['service_tsid'] = $serviceObj->service_tsid;
                        $dataArrayForDb['service_type_id'] = $serviceObj->service_type;
                        $this->serviceOption->saveServcieOptionFromKeyValuePairArray($dataArrayForDb);
                    }
                    return $itinararyServices;
                }
            }
        }
    }

    public function mapSessionActivityAndGuideKeysAndDBFieldNames()
    {
        return array(
            "activity_price" => "price",
            "itinerary_city_id" => "",
            "from_date" => "startDate",
            "to_date" => "",
            "activity_id" => "",
            "nights" => null
        );
    }

    function saveActivityAndGuide($sessionCity, $itineraryCity)
    {
        $itineraryActivities = [];
        foreach (array('activities', 'guides') as $serviceType) {
            foreach ($sessionCity[$serviceType] as $service) {
                if (array_key_exists('activity', $service)) {
                    $serviceObj = $this->activity->where('activity_tsid', '=', $service['activity']['activity_tsid'])->first();
                    $dbFieldNameToSessionKeyMapArray = $this->mapSessionActivityAndGuideKeysAndDBFieldNames();
                    $activityAndGuideDataForDbAsArray = $this->mapSessionArrayToDBFieldKeyAndValueArray($service, $dbFieldNameToSessionKeyMapArray);
                    $activityAndGuideDataForDbAsArray['from_date'] = $this->dateHelper->getMySqlDateFromNormalDate($activityAndGuideDataForDbAsArray['from_date']);
                    $activityAndGuideDataForDbAsArray['to_date'] = $this->dateHelper->addDaysToDate($activityAndGuideDataForDbAsArray['from_date'], $activityAndGuideDataForDbAsArray['nights']);
                    $activityAndGuideDataForDbAsArray['activity_id'] = $serviceObj->activity_id;
                    $activityAndGuideDataForDbAsArray['itinerary_city_id'] = $itineraryCity->itinerary_city_id;
                    $itinararyActivity = $this->itineraryActivity->saveItineraryActivityFromKeyValuePairArray($activityAndGuideDataForDbAsArray);
                    $this->saveExtras($service, $itinararyActivity);
                    $itineraryActivities[] = $itinararyActivity;
                    $selectedServiceOptions = $service['selectedServiceOptions'];
                    foreach ($selectedServiceOptions as $selectedServiceOption) {
                        $dbFieldNameToSessionKeyMapArray = $this->mapSessionServiceOptionsDataKeysAndDBFieldNames();
                        $dataArrayForDb = $this->mapSessionArrayToDBFieldKeyAndValueArray($selectedServiceOption, $dbFieldNameToSessionKeyMapArray);
                        $dataArrayForDb['itinerary_activity_id'] = $itinararyActivity->itinerary_activity_id;
                        $dataArrayForDb['service_id'] = $serviceObj->activity_id;
                        $dataArrayForDb['service_tsid'] = $serviceObj->activity_tsid;
                        $dataArrayForDb['service_type_id'] = $serviceObj->service_type;
                        $this->serviceOption->saveServcieOptionFromKeyValuePairArray($dataArrayForDb);
                    }
                }
            }
        }
        return $itineraryActivities;
    }

    public function saveItinerary($itinerary = null)
    {
        if (!$itinerary) {
            $itinerary = $this->saveItineraryLevelData();
        }
        $this->dataMapperHelper->removeAllItinerarRelatedServices($itinerary);
        $this->saveIternalServices($itinerary);
        $this->saveCarTypeServices($itinerary);
        $this->saveTourManager($itinerary);
        $this->saveCityLevelDataForDB($itinerary);
        return $itinerary;
    }

    public function cloneItinerary($itinerary)
    {
        $clone  = $itinerary->replicate();  
        $clone->save();
        return $this->saveItinerary($clone);
    }

}
