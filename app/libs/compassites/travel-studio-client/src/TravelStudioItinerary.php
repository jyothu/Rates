<?php

namespace Compassites\TravelStudioClient;

use Compassites\DateHelper\DateHelper;
use Compassites\TsBookingIdPoolHelper\TsBookingIdPoolHelper;
use Compassites\EnvironmentHelper\EnvironmentHelper;
use Compassites\ServiceRulesHelper\ServiceRulesHelper;
class TravelStudioItinerary extends TravelStudioClientBase {

    public $bookingFee = 0;
    public $bookingFee_option_2 = 0;
    public $currency = null;

    public function __construct(DateHelper $DateHelper, TsBookingIdPoolHelper $tsBookingIdPoolHelper, EnvironmentHelper $environmentHelper,ServiceRulesHelper $serviceRulesHelper) {
        parent::__construct($DateHelper, $tsBookingIdPoolHelper, $environmentHelper,$serviceRulesHelper);
    }

    function formatted_date($data) {
        $results = '';
        if (!empty($data)) {
            $data = substr($data, 0, 10);
            $results = date('Ymd', strtotime($data));
        }
        return $results;
    }

    function get_duration($start_date, $end_date) {
        $results = 0;
        if (!empty($start_date) && !empty($end_date)) {
            $start_date = substr($start_date, 0, 10);
            $end_date = substr($end_date, 0, 10);
            $dStart = new \DateTime($start_date);
            $dEnd = new \DateTime($end_date);
            $dDiff = $dStart->diff($dEnd);

            $results = $dDiff->days;
        }
        return $results;
    }

    function option1FeeCalculation($amt = parseAndSaveBookingDetailsResponseForData0, $service = '') {
        $this->bookingFee += $amt;
    }

    function option2FeeCalculation($amt = 0, $service = '') {
        $this->bookingFee_option_2 +=$amt;
    }

    function getFormattedBookingDetails($rawResponse) {
        $bookingInfo = array();
        $result = array();
        $enc = $rawResponse->BookingInfoResponses->ResponseList->anyType->enc_value;
        if (isset($enc)) {
            $bookingInfo = array('startDate' => $enc->BookingStartDate,
                'endDate' => $enc->BookingEndDate,
                'currency' => $enc->CurrencyISOcode,
                'BookingFee' => $enc->BookingFee,
                'username' => $enc->SECONDARY_SYSTEM_USER_NAME,
                'language' => $enc->SALES_ANALYSIS_1_NAME,
            );
            $bookedServiceList = $enc->BookedServices->BookedService;
//            echo "<pre>"; print_r($bookedServiceList); echo "</pre>"; exit;
            /*
             * Service ordering based on Date 
             */
            foreach ($bookedServiceList as $key => $bookedService) {
                $region = \Region::where('region_tsid', '=', $bookedService->RegionID)->first();

                $amt = 0;
                $serviceFromDate = '';
                $serviceEndDate = '';
                if (is_array($bookedService->BookedOptions->BookedOption)) {
                    foreach ($bookedService->BookedOptions->BookedOption as $key => $bookedOptionList) {
                        $amt += $bookedOptionList->Amount;
                        
                        if(empty($serviceFromDate) || $serviceFromDate > $bookedOptionList->FromDate ){
                            $serviceFromDate = $bookedOptionList->FromDate;
                        }
                        if(empty($serviceEndDate) || $serviceEndDate < $bookedOptionList->ToDate ){
                            $serviceEndDate = $bookedOptionList->ToDate;
                        }
                    }
                    $bookedService->BookedOptions->BookedOption = $bookedService->BookedOptions->BookedOption[0];
                } else {
                    $amt = $bookedService->BookedOptions->BookedOption->Amount;
                    $serviceFromDate = $bookedService->BookedOptions->BookedOption->FromDate;
                    $serviceEndDate = $bookedService->BookedOptions->BookedOption->ToDate;
//                    $bookedService->BookedOptions->BookedOption = $bookedService->BookedOptions->BookedOption;
                }
//                if (empty($region->region_parent_id) ) {
//                $this->option1FeeCalculation($amt, $bookedService->ServiceName);
//                $this->option2FeeCalculation($amt, $bookedService->ServiceName);
//                    continue;
//                }
                
                if (!$region) {
                    echo "<pre>"; 
                    //print_r($bookedService); 
                    echo "Location not available in TravelBuilder Database - ID : " . $bookedService->RegionID;
                    echo "</pre>"; exit;
                }


                if ($bookedService->ServiceTypeName != 'Accommodation') {
                    $this->option1FeeCalculation($amt, $bookedService->ServiceName);
                    $this->option2FeeCalculation($amt, $bookedService->ServiceName);
                }
                    
                $details = array('RegionID' => $bookedService->RegionID,
                    'RegionParentId' => (int) $region->region_parent_id,
                    'RegionName' => $region->region_name,
                    'ServiceTypeName' => $bookedService->ServiceTypeName,
                    'ServiceID' => $bookedService->ServiceID,
                    'ServiceTypeID' => $bookedService->ServiceTypeID,
                    'ServiceName' => $bookedService->ServiceName,
                    'BookedServiceID' => $bookedService->BookedServiceID,
                    'FromDate' => $serviceFromDate,
                    'ToDate' => $serviceEndDate,
                    'CurrencyISOCode' => $bookedService->BookedOptions->BookedOption->CurrencyISOCode,
                    'CostAmount' => $amt,
                );
                $key_list = str_replace("-", "", substr($bookedService->BookedOptions->BookedOption->ToDate, 0, 10)) . $bookedService->BookedServiceID;
                $resultOrderByDays[substr($bookedService->BookedOptions->BookedOption->FromDate, 0, 10)][$key_list] = $details;
            }
            $result1 = '';
            ksort($resultOrderByDays, SORT_REGULAR);
            $previousRegionID = '';
            $i = 1;
            $j = 0;
            foreach ($resultOrderByDays as $resultOrderByDayList) {

                ksort($resultOrderByDayList, SORT_REGULAR);
                $resultOrderByDayList_reorder = array();
                # if service is same as previous service, add into same list
                if (!empty($previousRegionID)) {
                    foreach ($resultOrderByDayList as $key => $resultOrderByDayDetail) {
                        if ($previousRegionID == $resultOrderByDayDetail['RegionID']) {
                            $resultOrderByDayList_reorder[] = $resultOrderByDayDetail;
                            unset($resultOrderByDayList[$key]);
                        }
                    }
                }

                $resultOrderByDayList = array_merge($resultOrderByDayList_reorder, $resultOrderByDayList);

                foreach ($resultOrderByDayList as $key => $resultOrderByDayDetail) {
                    if (!empty($previousRegionID) && !empty($resultOrderByDayDetail['RegionParentId']) && $previousRegionID != $resultOrderByDayDetail['RegionID'] && $j > 0) {
                        $i++;
                    }
                    $result[$i][] = $resultOrderByDayDetail;
                    $previousRegionID = (!empty($resultOrderByDayDetail['RegionParentId'])) ?
                            $resultOrderByDayDetail['RegionID'] : $previousRegionID;
                    $j++;
                }
            }
        }
        return array('bookingInfo' => $bookingInfo, 'services' => array_values($result));
    }

    function getPostData($name = '', $type = 'city') {
        return $name;
    }

    function getPostDataByServiceID($serviceId = '', $type = 'city') {
        return $serviceId;
    }

    function build_service_list($param = array()) {
        $hotel_option_1_amt = 0;
        $hotel_option_2_amt = 0;
        $number_of_nights = 0;
        $result = array();
        $result['city_activities'] = array();
        $result['city_services'] = array();
        $last_service = array();
        $date_list = array();
        foreach ($param as $key => $list) {
            $last_service = $list;
            array_push($date_list, $list['FromDate']);
            array_push($date_list, $list['ToDate']);
            $list['ServiceTypeName'] = trim($list['ServiceTypeName']);
            $this->currency = $list['CurrencyISOCode'];
            if ($list['ServiceTypeName'] == 'Accommodation') {
                $hotel = $list['ServiceID'];
                if (empty($hotel)) {
                    $hotel = -1;
                }
                if (!isset($result['hotel'])) {
                    $result['hotel'] = $hotel;
                    $this->option1FeeCalculation($list['CostAmount'], $list['ServiceName'] . 'from accommodation ');
                    $hotel_option_1_amt = $list['CostAmount'];
                } else {
                    $result['hotel_option_2'] = $hotel;
                    $hotel_option_2_amt = $list['CostAmount'];
                    $this->option2FeeCalculation($list['CostAmount'], $list['ServiceName']);
                }
            } else if ($list['ServiceTypeName'] == 'Activity') {
                $activities = $list['ServiceID'];
                if ($activities && !in_array($activities, (array) $result['city_activities'])) {
                    $result['city_activities'][] = $activities;
                }
            } else {
                $activities = $list['ServiceID'];
                if ($activities && !in_array($activities, (array) $result['city_services'])) {
                    $result['city_services'][] = $activities;
                }
            }
        }
        $number_of_nights = $this->get_duration(min($date_list), max($date_list));
        # City    
        $result['city'] = $last_service['RegionID'];

        # if city not have second hotel option
        if (!isset($result['hotel_option_2'])) {
            $this->option2FeeCalculation($hotel_option_1_amt, $list['ServiceName']);
        } else if ($hotel_option_2_amt && $hotel_option_2_amt < $hotel_option_1_amt) {
            $temp = $result['hotel'];
            $result['hotel'] = $result['hotel_option_2'];
            $result['hotel_option_2'] = $temp;

            $this->option1FeeCalculation(-$hotel_option_1_amt, $list['ServiceName']);
            $this->option1FeeCalculation($hotel_option_2_amt, $list['ServiceName']);
            $this->option2FeeCalculation(-$hotel_option_2_amt, $list['ServiceName']);
            $this->option2FeeCalculation($hotel_option_1_amt, $list['ServiceName']);
        }
        $result = array_filter($result);
        $result['number_of_nights'] = $number_of_nights;
        $result['start_date'] = min($date_list);
        $result['end_date'] = max($date_list);
        return $result;
    }

    function api_itinerary_creation($bookingId) {
        $activities = '';
        if (isset($bookingId) && !empty($bookingId)) {
            $soapReponse = $this->getBookingDetails($bookingId);
            if (!empty($soapReponse['bookingInfo'])) {
                $post_title = $bookingId;
                $ts_start_date = $this->formatted_date($soapReponse['bookingInfo']['startDate']);
                $ts_end_date = $this->formatted_date($soapReponse['bookingInfo']['endDate']);
                $duration_in_days = $this->get_duration($soapReponse['bookingInfo']['startDate'], $soapReponse['bookingInfo']['endDate']);
                $duration_in_days = $duration_in_days + 1;
                $duration = "{$duration_in_days} days - " . ($duration_in_days - 1) . " nights";
                $enquiry_id = $bookingId;
                $language = '';
                $data = array(
                    'post_content' => $activities,
                    'post_title' => $post_title,
                    'post_status' => 'publish',
                    'post_type' => 'itinerary',
                    'post_author' => 1,
                );
                $value = array();
                $previous_city_service = array();
                foreach ($soapReponse['services'] as $key => $services) {
                    $temp = $this->build_service_list($services);
                    $temp_service = array_key_exists('city_services', $temp);
                    unset($temp['city_services']);
                    if (!empty($previous_city_service)) {
                        $temp['city_services'] = $previous_city_service;
                    }
                    $value[] = $temp;
                    $previous_city_service = $temp_service;
                }
                foreach ($value as $i => $services) {
                    if ($i && empty($value[$i - 1]['hotel'])) {
                        if ($value[$i - 1]['city_activities']) {
                            $value[$i]['city_activities'] = array_merge($value[$i - 1]['city_activities'], (array) $value[$i]['city_activities']);
                        }
                        if ($value[$i - 1]['city_services']) {
                            $value[$i]['city_services'] = array_merge($value[$i - 1]['city_services'], (array) $value[$i]['city_services']);
                        }
                        unset($value[$i - 1]);
                    }
                }
                $this->costPerPersonOption1 = $this->bookingFee;
                $this->costPerPersonOption2 = $this->bookingFee_option_2;
            }
        }
        return $value;
    }

    function saveBookingInDb($services, $itinerary) {
        $itineraryCity = new \ItineraryCity();
        $itineraryActivity = new \ItineraryActivity();
        $itenararyInternalService = new \ItenararyInternalService();
        foreach ($services as $regionServices) {
            $regionObj = null;  
            $RegionID = 0;
            for ($i=count($regionServices) - 1; $i>=0 ; $i--) {  
                if(!empty($regionServices[$i]['RegionParentId'])){
                    $RegionID = $regionServices[$i]['RegionID'];
                    break;
                }
            }
            if ($RegionID > 0) {
                $regionObj = \Region::where("region_tsid", "=", $RegionID)->first();
                if ($regionObj) {
                    $itineraryCityObj = $itineraryCity->saveCity($itinerary, 0, null, null, 0, 0, null, null, $regionObj);
                }
            }

            $itinararyServices = array();
            $itineraryActivities = array();
            $intineraryInternalServcies = array();
            $date_list = array();
            $hotel1_tsid = null;
            $hotel2_tsid = null;
            foreach ($regionServices as $regionService) {
                $itinararyService = new \ItinararyService();
                $service_tsid = $regionService["ServiceID"];
                $service_price = $regionService["CostAmount"];
                $fromDate = $this->dateHelper->removeTimeFromTMDate($regionService["FromDate"]);
                $toDate = $this->dateHelper->removeTimeFromTMDate($regionService["ToDate"]);
                $nights = $this->dateHelper->dateDifferenceInDays($fromDate, $toDate);
                $serviceTypeID = $regionService["ServiceTypeID"];
                if($RegionID == $regionService["RegionID"]){
                array_push($date_list, $fromDate);
                array_push($date_list, $toDate);
                }
                $option_type = 1;

                if ($serviceTypeID == 2) {
                    if (!$hotel1_tsid) {
                        $hotel1_tsid = $service_tsid;
                        $hotel1_price = $service_price;
                    } else {
                        $hotel2_tsid = $service_tsid;
                        $hotel2_price = $service_price;
                    }
                } elseif ($serviceTypeID == 3 || $serviceTypeID == 5) {
                    $activityObj = \Activity::where("activity_tsid", "=", $service_tsid)->first();
                    if ($activityObj) {
                        $itineraryActivities[] = $itineraryActivity->prepareItinararyActivity($activityObj, 0, 0, $fromDate, $toDate, $nights+1, $service_price);
                    }
                } elseif ($serviceTypeID == 20) {
                    $internalServiceObj = \InternalService::where("service_tsid", "=", $service_tsid)->first();
                    if ($internalServiceObj) {
                        $intineraryInternalServcies[] = $itenararyInternalService->prepareItinararyInternalService($itinerary, $internalServiceObj, $service_price);
                    }
                } else {
                    $serviceObj = \Service::where("service_tsid", "=", $service_tsid)->first();
                    if ($serviceObj) {
                        $itinararyServices[] = $itinararyService->prepareItinararyService($serviceObj, $service_price, $option_type, $fromDate, $toDate);
                    }
                }
            }
            if (count($intineraryInternalServcies) > 0) {
                $itinerary->internalServices()->saveMany($intineraryInternalServcies);
            }
            if (count($itinararyServices) > 0) {
                $itineraryCityObj->services()->saveMany($itinararyServices);
            }
            if (count($itineraryActivities) > 0) {
                $itineraryCityObj->activities()->saveMany($itineraryActivities);
            }
            $number_of_nights = $this->get_duration(min($date_list), max($date_list));
            $itineraryCityObj->from_date = min($date_list);
            $itineraryCityObj->to_date = max($date_list);
            $itineraryCityObj->number_of_nights = $number_of_nights;
            $hotel1Obj = \Hotels::where("hotel_tsid", "=", $hotel1_tsid)->first();
            $hotel2Obj = \Hotels::where("hotel_tsid", "=", $hotel2_tsid)->first();
            if ($hotel1Obj && $hotel2Obj) {
                if ($hotel1_price > $hotel2_price) {
                    $itineraryCityObj->hotel1()->associate($hotel2Obj);
                    $itineraryCityObj->hotel2()->associate($hotel1Obj);

                    $itineraryCityObj->hotel1_price = $hotel2_price;
                    $itineraryCityObj->hotel2_price = $hotel1_price;
                } else {
                    $itineraryCityObj->hotel1()->associate($hotel1Obj);
                    $itineraryCityObj->hotel2()->associate($hotel2Obj);

                    $itineraryCityObj->hotel1_price = $hotel1_price;
                    $itineraryCityObj->hotel2_price = $hotel2_price;
                }
            } else {
                if ($hotel1Obj) {
                    $itineraryCityObj->hotel1_price = $hotel1_price;
                    $itineraryCityObj->hotel1()->associate($hotel1Obj);
                }
                if ($hotel2Obj) {
                    $itineraryCityObj->hotel1_price = $hotel2_price;
                    $itineraryCityObj->hotel1()->associate($hotel2Obj);
                }
            }

            $itineraryCityObj->save();
        }
        return $itinerary;
    }

    function assignServicesToTheirRespectiveCity($cityServices) {
        $newCityServices = array();
        if (!empty($cityServices)) {
            $serviceCount = count($cityServices);
            for ($cityIndex = 0; $cityIndex < $serviceCount - 1; $cityIndex++) {
                $city = $cityServices[$cityIndex];
                $nextCityIndex = $cityIndex + 1;
                $newCityServices[$nextCityIndex] = array();
                $arrivalDetailsTypeArray = array(4, 6, 8, 9, 12, 13, 14, 21);
                foreach ($city as $serviceIndex => $service) {
                    $serviceTypeID = $service['ServiceTypeID'];
                    if (in_array($serviceTypeID, $arrivalDetailsTypeArray)) {
                        $newCityServices[$nextCityIndex][] = $service;
                    } else {
                        $newCityServices[$cityIndex][] = $service;
                    }
                }
                
            }
//            echo "<pre>"; print_r($newCityServices); echo "<br/>** $nextCityIndex **<br/>"; print_r($cityServices); echo "</pre>"; exit;
            $newCityServices[$nextCityIndex] = array_merge($newCityServices[$nextCityIndex],$cityServices[$nextCityIndex]);
             
        }
        ksort($newCityServices);
        
        return $newCityServices;
    }

    function mergeCitiesRepeatingSequentially($reParsedServicesCities) {
        $previousRegionID = null;
        $cityToBeMergedToIndex = null;
        foreach ($reParsedServicesCities as $cityIndex => $cityServcies) {
            $RegionID = $cityServcies[0]['RegionID'];
            if ($previousRegionID != $RegionID) {
                $previousRegionID = $RegionID;
                $cityToBeMergedToIndex = $cityIndex;
            } else {
                $reParsedServicesCities[$cityToBeMergedToIndex] = array_merge($reParsedServicesCities[$cityToBeMergedToIndex], $reParsedServicesCities[$cityIndex]);
                unset($reParsedServicesCities[$cityIndex]);
            }
        }
        return array_values($reParsedServicesCities);
    }

    function removeCityNotAssignHotel($cityServices) {

        $result = array();
        
        if(!empty($cityServices)){
            $serviceCount = count($cityServices);

            for ($i = 1; $i < $serviceCount; $i++) {
                $serviceList = $cityServices[$i-1];
                # Checking Accommodation
                $accommodationCheck = 0;
                foreach ($serviceList as $serviceKey => $services) {
                    if ($services['ServiceTypeID'] == 2) {
                         $accommodationCheck = 1;
                    }
                }
                # End
                # Remove the city list if not exit previous city 
                if ($accommodationCheck == 0) {
                    $cityServices[$i] = array_merge($cityServices[$i -1], $cityServices[$i]);
                    unset($cityServices[$i - 1]);
                }
                # End
            }
            $result = array_values($cityServices);
        }
        
        return $result;
    }

}
