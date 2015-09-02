<?php

/*
 * This file is part of TravelBuilder v0.4 project
 */

namespace Compassites\CreateItinerarySessionHelper;

use Illuminate\Session\Store;
use Compassites\DateHelper\DateHelper;
use Compassites\ItineraryValidationHelper\ItineraryValidationHelper;

/**
 * It helps to save itinerary from 
 * session data which comprises of cities, hotels, arrival details, internal services,
 * car type, tour manager etc..
 * 
 * @package CreateItinerarySessionHelper
 * @author VIPIN ps <vipinps13@gmail.com>
 * @version 1.0
 */
class CreateItinerarySessionHelper extends CreateItinerarySessionHelperBase
{

    /**
     * Initialises services required for saving itinerary data
     * @param Store $session To hold session service object     
     */
    protected $dateHelper;
    protected $currencyCode;

    public function __construct(Store $session, DateHelper $dateHelper, \Itinerary $itinerary, \TravelStudio $travelStudio,ItineraryValidationHelper $itineraryValidationHelper)
    {
        $this->travelStudio = $travelStudio;
        $this->itinerary = $itinerary;
        $this->session = $session;
        $this->dateHelper = $dateHelper;
        $this->itineraryValidationHelper = $itineraryValidationHelper;
    }

    public function getItinerary($itinerary_id)
    {
        $itinerary = $this->itinerary->find($itinerary_id);
        $this->currencyCode = $itinerary->currency;
        $itinerarySessionArray = array('itinerary' =>
            array(
                'itinerary_id' => $itinerary->itinerary_id,
                'adult' => $itinerary->adult,
                'child' => $itinerary->child,
                'startdate' => $this->dateHelper->getNormalDateFromMySqlDate($itinerary->start_date),
                'enddate' => $this->dateHelper->getNormalDateFromMySqlDate($itinerary->end_date),
                'nights' => $itinerary->number_of_nights,
                'passenger' => $itinerary->passenger,
                'currency' => $itinerary->currency,
                'ts_booking_id' => $itinerary->ts_booking_id,
                'v3_itinerary_id' => $itinerary->v3_itinerary_id,
            ),
            'price1' => $itinerary->option1_price,
            'price2' => $itinerary->option2_price,
        );
        foreach ($itinerary->cities as $i => $itineraryCity) {
            $dateOnWhichServiceIsRequired = $this->dateHelper->getNormalDateFromMySqlDate($itineraryCity->from_date);
            $nightCount = (int) $itineraryCity->number_of_nights;
            $itinerarySessionArray['city'][$i] = array(
                'region_id' => $itineraryCity->region_id,
                'region_tsid' => $itineraryCity->region->region_tsid,
                'region_name' => $itineraryCity->region->region_name,
                'nightCount' => $nightCount,
                'start-date' => $dateOnWhichServiceIsRequired,
                'end-date' => $this->dateHelper->getNormalDateFromMySqlDate($itineraryCity->to_date),
            );
            $itinerarySessionArray['city'][$i]['optionone']['arrivalDetail'] = [];
            $itinerarySessionArray['city'][$i]['optiontwo']['arrivalDetail'] = [];
            $itinerarySessionArray['city'][$i]['optionone']['hotel'] = [];
            $itinerarySessionArray['city'][$i]['optiontwo']['hotel'] = [];
            foreach (array('optionone' => $itineraryCity->option1, 'optiontwo' => $itineraryCity->option2) as $option => $hotelAndServices) {
                foreach ($hotelAndServices['services'] as $itinararyService) {
                    $serviceOptions = $itinararyService->serviceOptions;
                    $arrivalDetail = array();
                    $serviceExtrasLabel=[];
                    if ($itinararyService->service && $itinararyService->service->service_id > 0 && $itinararyService->service->service_type > 0) {
                        $arrivalDetail['arrivalDetailsServiceType'] = array(
                            'service_type_id' => $itinararyService->service->service_type,
                            'ts_service_type_id' => $itinararyService->service->service_type,
                            'service_type_name' => $itinararyService->service->serviceType->service_type_name
                        );

                        $serviceTypeObj  = json_decode($itinararyService->service->serviceType->toJson(),true);
                        $arrivalDetail['arrivalDetailsService'] = array(
                            'service_id' => $itinararyService->service->service_id,
                            'service_tsid' => $itinararyService->service->service_tsid,
                            'service_name' => $itinararyService->service->service_name,
                            'service_type' => $serviceTypeObj
                        );
                        $selectedhotelExtras=[];
                        foreach ($itinararyService->serviceExtras as $serviceExtras) {
                            $selectedhotelExtras[] = array(
                                "ServiceTypeExtraName" => $serviceExtras->extras_name,
                                "ServiceExtraId" => (int) $serviceExtras->extras_id,
                                "OccupancyTypeID" => 0,
                                "ServiceTypeTypeID" => 3,
                                "ServiceTypeTypeName" => "",
                                "ExtraMandatory" => false,
                                "MaxChild" => 0,
                                "MaxAdults" => 100,
                                "TOTALPRICE" => (int) $serviceExtras->extras_price,
                                "quantity" => $serviceExtras->quantity
                            );
                            $serviceExtrasLabel[] = $serviceExtras->extras_name;
                        }
                        $arrivalDetail['selectedserviceExtras'] =$selectedhotelExtras;
                        $arrivalDetail['serviceExtraLabel'] =implode(",",$serviceExtrasLabel);
                    }
                    $arrivalDetail['servicePrice'] = $itinararyService->service_price;
                    $arrivalDetail['optionForService'] = [];
                    if (count($serviceOptions) > 0) {
                        $arrivalDetail['optionForService'] = array(
                            'ServiceOptionName' => $serviceOptions[0]->option_name,
                            'TotalSellingPrice' => $serviceOptions[0]->option_price,
                            'OptionID' => (int) $serviceOptions[0]->option_id,
                            'MaxAdult' => 0,
                            'MaxChild' => 0,
                            'Occupancy' =>(int) $serviceOptions[0]->occupancy,
                            'quantity' => $serviceOptions[0]->quantity,
                            'adultCount' => 0,
                            'childCount' => 0
                        );
                    }
                    $itinerarySessionArray['city'][$i][$option]['arrivalDetail'][] = $arrivalDetail;
                }
                $hotel = $hotelAndServices['hotel'];
                if ($hotel) {
                    $this->travelStudioClient = \Illuminate\Support\Facades\App::make('TravelStudio');
                    $this->travelStudioClient->setValidRoomTypesForTheHotel(\HotelRoomType::where("room_type_tsid", "<", 9)->get());
                    $this->travelStudioClient->getServicesPricesAndAvailability($hotel->hotel_tsid, 2, $dateOnWhichServiceIsRequired, $nightCount, null);
                    $allServiceOptionsForHotel = $this->travelStudioClient->serviceOptions;
                    $allServiceExtrasForHotel = $this->travelStudioClient->getExtrasForAService(2, $hotel->hotel_tsid);
                    $hotelOptions = $hotelAndServices['hotelServiceOptions'];
                    $hotelExtras = $hotelAndServices['hotelServiceExtras'];
                    $optionNum = $option == "optionone" ? 1 : 2;
                    $optionPriceAttribute = "hotel{$optionNum}_price";
                    $hotelArray = array(
                        'hotel_tsid' => $hotel->hotel_tsid,
                        'hotel_name' => $hotel->hotel_name,
                        'hotel_id' => $hotel->hotel_id,
                        'selectedHotelPrice' => $itineraryCity->$optionPriceAttribute,
                        'hotelExtraLabel' => '',
                    );
                    $hotelOptionLabel = [];
                    $hotelExtraLabel = [];
                    $selectedhotelOptions = [];
                    $selectedhotelExtras = [];
                    foreach ($hotelOptions as $hotelOption) {
                        $MaxAdult = 1;
                        $MaxChild = 1;
                        $Occupancy = 2;
                        $matchFromHaystack = $this->getMatchOfNeedleFromHaystack($hotelOption, $allServiceOptionsForHotel, 'option_id', 'OptionID');
                        if ($matchFromHaystack) {
//                            $MaxAdult = $matchFromHaystack['MaxAdult'];
//                            $MaxChild = $matchFromHaystack['MaxChild'];
//                            $Occupancy = $matchFromHaystack['Occupancy'];
//                            $allServiceOptionsForHotel[$this->matchKey]['quantity'] = $hotelOption->quantity;
//                            $allServiceOptionsForHotel[$this->matchKey]['adultCount'] = $hotelOption->adult_count;
//                            $allServiceOptionsForHotel[$this->matchKey]['childCount'] = $hotelOption->child_count;
                              $TotalSellingPrice = $matchFromHaystack['TotalSellingPrice'];
                        }else{
                            $TotalSellingPrice = $hotelOption->option_price;
                        }
                        
//                        $TotalSellingPrice = $allServiceOptionsForHotel[$this->matchKey]['TotalSellingPrice'] > 0 ? $allServiceOptionsForHotel[$this->matchKey]['TotalSellingPrice'] : $hotelOption->option_price;
                        $selectedhotelOptions[] = array(
                            'ServiceOptionName' => $hotelOption->option_name,
                            'TotalSellingPrice' => (int) $TotalSellingPrice * $hotelOption->quantity,
                            'OptionID' => $hotelOption->option_id,
                            'MaxAdult' => $MaxAdult,
                            'MaxChild' => $MaxChild,
                            'Occupancy' =>  $hotelOption->occupancy,
                            'quantity' => $hotelOption->quantity,
                            'adultCount' => $hotelOption->adult_count,
                            'childCount' => $hotelOption->child_count
                        );
                        $hotelOptionLabel[] = $hotelOption->option_name;
                    }
                    foreach ($hotelExtras as $hotelExtra) {
                        $selectedhotelExtras[] = array(
                            "ServiceTypeExtraName" => $hotelExtra->extras_name,
                            "ServiceExtraId" => (int) $hotelExtra->extras_id,
                            "OccupancyTypeID" => 0,
                            "ServiceTypeTypeID" => 3,
                            "ServiceTypeTypeName" => "",
                            "ExtraMandatory" => false,
                            "MaxChild" => 0,
                            "MaxAdults" => 100,
                            "TOTALPRICE" => (int) $hotelExtra->extras_price / $hotelExtra->quantity,
                            "quantity" => $hotelExtra->quantity
                        );
                        $hotelExtraLabel[] = $hotelExtra->extras_name;
                    }
                    $hotelArray['hotelOptionLabel'] = implode(", ", $hotelOptionLabel);
                    $hotelArray['hotelExtraLabel'] = implode(", ", $hotelExtraLabel);
                    $itinerarySessionArray['city'][$i][$option]['hotel'] = $hotelArray;
                    $itinerarySessionArray['city'][$i][$option]['hotel']['selectedhotelOptions'] = $selectedhotelOptions;
                    $itinerarySessionArray['city'][$i][$option]['hotel']['hotelOptions'] = $allServiceOptionsForHotel;
                    $itinerarySessionArray['city'][$i][$option]['hotel']['selectedhotelExtras'] = $selectedhotelExtras;
                    $itinerarySessionArray['city'][$i][$option]['hotel']['hotelExtras'] = $allServiceExtrasForHotel;
                }
            }
            ## Activities
//            echo "<pre>";

            $itineraryCityActivities = array();
            $itineraryCityGuides = array();
            foreach ($itineraryCity->activities()->getResults() as $itinararyCityActivityIndex => $itinararyCityActivity) {

                $activities['activity'] = array(
                    'activity_tsid' => $itinararyCityActivity->activity()->getResults()->activity_tsid,
                    'activity_name' => $itinararyCityActivity->activity()->getResults()->activity_name,
                    'activity_price' => $itinararyCityActivity->activity()->getResults()->activity_price,
                    'activity_type' => $itinararyCityActivity->activity()->getResults()->service_type
                );
                $activities['price'] = $itinararyCityActivity->activity_price;

                $activities['startDate'] = $this->dateHelper->getNormalDateFromMySqlDate($itinararyCityActivity->from_date);
                $activities['nights'] = $itinararyCityActivity->nights;
                $optExtras = $this->getAllExtrasAndOptions($itinararyCityActivity->activity()->getResults()->activity_tsid, $itinararyCityActivity->activity()->getResults()->service_type, $itinararyCityActivity->from_date);
                $selectedServiceOptions = array();
                if (!empty($itinararyCityActivity->serviceOptions()->getResults())) {

                    foreach ($itinararyCityActivity->serviceOptions()->getResults() as $serviceOptionsKey => $serviceOption) {
                        $selectedServiceOptions[] = array(
                            "ServiceOptionName" => $serviceOption->option_name,
                            "TotalSellingPrice" => $serviceOption->option_price,
                            "OptionID" => $serviceOption->option_id,
                            "MaxAdult" => $serviceOption->max_adult,
                            "MaxChild" => $serviceOption->max_child,
                            "Occupancy" => $serviceOption->occupancy,
                            "quantity" => $serviceOption->quantity,
                            "adultCount" => $serviceOption->adult_count,
                            "childCount" => $serviceOption->child_count,
                        );
                    }
                }
                $options = $this->replaceMatchReplacableWithReplaced($optExtras['options'], $selectedServiceOptions, 'OptionID');
                $activities['serviceOptions'] = $options;
                $activities['selectedServiceOptions'] = $selectedServiceOptions;
                $selectedServiceExtras = [];

                if (!empty($itinararyCityActivity->serviceExtras()->getResults())) {

                    foreach ($itinararyCityActivity->serviceExtras()->getResults() as $serviceExtrasKey => $serviceExtra) {
                        $selectedServiceExtras[] = array(
                            "ServiceTypeExtraName" => $serviceExtra->extras_name,
                            "ServiceExtraId" => $serviceExtra->extras_id,
                            "OccupancyTypeID" => $serviceExtra->option_type,
                            "ExtraMandatory" => false,
                            "MaxAdult" => $serviceExtra->max_adult,
                            "MaxChild" => $serviceExtra->max_child,
                            "quantity" => $serviceExtra->quantity,
                            "adultCount" => 0,
                            "childCount" => 0,
                            "TOTALPRICE" => $serviceExtra->extras_price,
                        );
                    }
                }
                $extras = $this->replaceMatchReplacableWithReplaced($optExtras['extras'], $selectedServiceExtras, 'ServiceExtraId');
                $activities['serviceExtras'] = $extras;

                $activities['selectedServiceExtra'] = $selectedServiceExtras;


//                print_r($activities);

                $serviceType = $itinararyCityActivity->activity()->getResults()->serviceType();
                if ($serviceType->getResults()->service_type_name == 'Activity') {
                    $itineraryCityActivities[] = $activities;
                } else {
                    $itineraryCityGuides[] = $activities;
                }
            }
            $itinerarySessionArray['city'][$i]['activities'] = $itineraryCityActivities;
            $itinerarySessionArray['city'][$i]['guides'] = $itineraryCityGuides;
//            exit;
        }
        $itinerarySessionArray['itinerary']['otherDetails'] = $this->getOtherServices($itinerary);
        $itinerarySessionArray['itinerary']['otherDetails']['amnt_option1'] = $itinerary->adjustment1;
        $itinerarySessionArray['itinerary']['otherDetails']['amnt_option2'] = $itinerary->adjustment2;
        $itinerarySessionArray['itinerary']['otherDetails']['remarks'] = $itinerary->remarks;

        return $itinerarySessionArray;
    }

    function getOtherServices($itinerary)
    {
        $otherServices = [];
        foreach ($itinerary->internalServices as $i => $itineraryInternalService) {
            $intService = [
                "guests" => [
                    "adult" => $itineraryInternalService->adult_count,
                    "child" => $itineraryInternalService->child_count,
                    "label" => $itineraryInternalService->passenger
                ],
                "price" => $itineraryInternalService->internal_service_price,
                "startDate" => $this->dateHelper->getNormalDateFromMySqlDate($itineraryInternalService->start_date),
                "internalServicesOptions" => [
                    "service_tsid" => $itineraryInternalService->internalService->service_tsid,
                    "service_name" => $itineraryInternalService->internalService->service_name,
                    "service_price" => $itineraryInternalService->internal_service_price,
                    "service_type" => $itineraryInternalService->internalService->service_type,
                ],
                "nights" => $itineraryInternalService->nights,
            ];
            $optExtras = $this->getAllExtrasAndOptions($itineraryInternalService->internalService->service_tsid, $itineraryInternalService->internalService->service_type, $itineraryInternalService->start_date);
            $options = $this->getServiceOptions($itineraryInternalService->serviceOptions);
            $extras = $this->getServiceExtras($itineraryInternalService->serviceExtras);
            $optExtras['options'] = $this->replaceMatchReplacableWithReplaced($optExtras['options'], $options, 'OptionID');
            $optExtras['extras'] = $this->replaceMatchReplacableWithReplaced($optExtras['extras'], $extras, 'ServiceExtraId');
            $intService['internalService'] = [
                'selectedServiceOptions' => $options,
                'selectedServiceExtra' => $extras,
                'serviceExtras' => $optExtras['extras'],
                'serviceOptions' => $optExtras['options']
            ];
            $otherServices['internalService'][$i] = $intService;
        }
        $carService = [];
        foreach ($itinerary->itinararyCarServices as $i => $itinararyCarService) {
            $options = $this->getServiceOptions($itinararyCarService->serviceOptions);
            $extras = $this->getServiceExtras($itinararyCarService->serviceExtras);
            $optExtras = $this->getAllExtrasAndOptions($itinararyCarService->service->service_tsid, $itinararyCarService->service->service_type, $itinararyCarService->start_date);
            $optExtras['options'] = $this->replaceMatchReplacableWithReplaced($optExtras['options'], $options, 'OptionID');
            $optExtras['extras'] = $this->replaceMatchReplacableWithReplaced($optExtras['extras'], $extras, 'ServiceExtraId');           
            $carService = [
                "price" => $itinararyCarService->service_price,
                "startDate" => $this->dateHelper->getNormalDateFromMySqlDate($itinararyCarService->from_date),
                "nights" => $itinararyCarService->nights,
                "carOptions" => [
                    "serviceOptions" => $optExtras['options'],
                    "serviceExtras" => $optExtras['extras'],
                    "selectedServiceExtra" => $extras,
                    "selectedServiceOptions" => $options,
                ],
                "carserviceOptions" => [
                    "service_tsid" => $itinararyCarService->service->service_tsid,
                    "service_name" => $itinararyCarService->service->service_name,
                    "service_price" => 0,
                    "service_type" => $itinararyCarService->service->service_type,
                ],
            ];
            $otherServices['carService'][$i] = $carService;
        }
        if ($itinerary->tourManager) {
            $optExtras = $this->getAllExtrasAndOptions($itinerary->tourManager->service->service_tsid, $itinerary->tourManager->service->service_type, $itinerary->tourManager->from_date);
            $options = $this->getServiceOptions($itinerary->tourManager->serviceOptions);
            $extras = $this->getServiceExtras($itinerary->tourManager->serviceExtras);

            $optExtras['options'] = $this->replaceMatchReplacableWithReplaced($optExtras['options'], $options, 'OptionID');
            $optExtras['extras'] = $this->replaceMatchReplacableWithReplaced($optExtras['extras'], $extras, 'ServiceExtraId');
            $tourManager = [
                "tourOptions" => [
                    "serviceOptions" => $optExtras['options'],
                    "serviceExtras" => $optExtras['extras'],
                    "selectedServiceExtra" => $extras,
                    "selectedServiceOptions" => $options,
                ],
                "price" => $itinerary->tourManager->service_price,
                "startDate" => $this->dateHelper->getNormalDateFromMySqlDate($itinerary->tourManager->from_date),
                "tourServiceOptions" => [
                    "service_tsid" => $itinerary->tourManager->service->service_tsid,
                    "service_name" => $itinerary->tourManager->service->service_name,
                    "service_price" => 0,
                    "service_type" => $itinerary->tourManager->service->serviceType->toArray(),
                ],
                "nights" => $itinerary->tourManager->nights,
            ];
            $otherServices['tourManager'][0] = $tourManager;
        }
        return $otherServices;
    }

    public function setItinerarySessionForEditing($itinerary_id)
    {
        $itinerarySessionArray = $this->getItinerary($itinerary_id);
        $itinerarySessionArray = $this->itineraryValidationHelper->validateItineraryHotelPriceForZeroValues($itinerarySessionArray);
        $this->session->put('itineraryData', $itinerarySessionArray);
    }

    function getMatchOfNeedleFromHaystack($needle, $hayStack, $comparisonKey, $comparisonKey2 =null)
    {
        if(!$comparisonKey2){
            $comparisonKey2 = $comparisonKey;
        }
        $this->matchKey = null;
        foreach ($hayStack as $i => $hayStackElement) {
            if ((int) $hayStackElement[$comparisonKey2] == (int) $needle[$comparisonKey]) {
                $this->matchKey = $i;
                return $hayStackElement;
            }
        }
    }

    function replaceMatchReplacableWithReplaced($replacableArray, $replacedWithArray, $comparisonKey)
    {
        foreach ($replacableArray as $i => $replacableItem) {
            foreach ($replacedWithArray as $j => $replacedItem) {
                if (array_key_exists($comparisonKey, $replacableItem) && array_key_exists($comparisonKey, $replacedItem)) {
                    if ((int) $replacableItem[$comparisonKey] == (int) $replacedItem[$comparisonKey]) {
                        $replacableArray[$i] = $replacedWithArray[$j];
                    }
                }
            }
        }
        return $replacableArray;
    }

    function getServiceExtras($serviceExtras)
    {
        $extras = [];
        foreach ($serviceExtras as $serviceExtra) {
            $extras[] = [
                "ServiceTypeExtraName" => $serviceExtra->extras_name,
                "ServiceExtraId" => (int) $serviceExtra->extras_id,
                "OccupancyTypeID" => 0,
                "ServiceTypeTypeID" => 0,
                "ServiceTypeTypeName" => "",
                "ExtraMandatory" => false,
                "MaxChild" => 0,
                "MaxAdults" => 0,
                "TOTALPRICE" => (int) $serviceExtra->extras_price,
                "quantity" => $serviceExtra->quantity
            ];
        }
        return $extras;
    }

    function getServiceOptions($serviceOptions, $isAccomodation = false)
    {
        $options = [];
        foreach ($serviceOptions as $serviceOption) {
            if ($isAccomodation) {
                $Occupancy = 2;
                $quantity = $serviceOption->quantity ? $serviceOption->quantity : 1;
                $adultCount = $serviceOption->adult_count;
                $childCount = $serviceOption->child_count;
            } else {
                $Occupancy = 0;
                $quantity = 0;
                $adultCount = 0;
                $childCount = 0;
            }
            $options[] = [
                'ServiceOptionName' => $serviceOption->option_name,
                'TotalSellingPrice' => (int) $serviceOption->option_price,
                'OptionID' => (int) $serviceOption->option_id,
                'MaxAdult' => 0,
                'MaxChild' => 0,
                'Occupancy' => $serviceOption->occupancy,
                'quantity' => $quantity,
                'adultCount' => $adultCount,
                'childCount' => $childCount
            ];
        }
        return $options;
    }

    function getAllExtrasAndOptions($serviceTsid, $serviceTypeId, $dateOnWhichServiceIsRequired, $nightsForWhichServiceIsRequired = null, $currencyCode = null)
    {
        $response = [];
        $limitToOneRoomType = false;
        if ($serviceTypeId == 2) {
            $limitToOneRoomType = true;
        }
        if (!$nightsForWhichServiceIsRequired) {
            $nightsForWhichServiceIsRequired = 1;
        }
        if (!$currencyCode) {
            $currencyCode = $this->currencyCode;
        }
        $this->travelStudio->getServicesPricesAndAvailability($serviceTsid, $serviceTypeId, $dateOnWhichServiceIsRequired, $nightsForWhichServiceIsRequired, $currencyCode, $limitToOneRoomType);
        $response['options'] = $this->travelStudio->serviceOptions;
        $toDate = $this->dateHelper->addDaysToDate($dateOnWhichServiceIsRequired, $nightsForWhichServiceIsRequired);
        $response['extras'] = $this->travelStudio->getExtrasForAService($serviceTypeId, $serviceTsid, $dateOnWhichServiceIsRequired, $toDate);
        return $response;
    }

}
