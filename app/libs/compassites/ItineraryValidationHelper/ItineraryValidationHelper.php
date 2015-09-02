<?php

/*
 * This file is part of TravelBuilder v0.4 project
 */

namespace Compassites\ItineraryValidationHelper;

use Compassites\DateHelper\DateHelper;

/**
 * It helps to validate itinerary data
 * 
 * @package ItineraryValidationHelper
 * @author Jeevan N <jeeeevz@gmail.com>
 * @version 1.0
 */
class ItineraryValidationHelper
{

    protected $dateHelper;
    public $totalCityStayDuration;
    public $itineraryStayDuration;
    public $isValid;
    public $hasErrors = false;
    private $validationType;

    public function __construct(DateHelper $dateHelper)
    {
        $this->dateHelper = $dateHelper;
    }

    public function validateItinerarySpanAgainstStaySpanInEachCity($sessionData)
    {
        $this->validationType = 1;
        $itineraryStayDuration = $sessionData['itinerary']['nights'];
        $totalCityStayDuration = 0;
        foreach ($sessionData['city'] as $city) {
            $totalCityStayDuration+=$city['nightCount'];
        }
        $this->totalCityStayDuration = (int) $totalCityStayDuration;
        $this->itineraryStayDuration = (int) $itineraryStayDuration;
        return $this->isValid = $this->totalCityStayDuration <= $this->itineraryStayDuration;
    }

    function getErrorMsg()
    {
        if ($this->validationType == 1) {
            return "Your stay should not exceed {$this->itineraryStayDuration} days, but is {$this->totalCityStayDuration} days";
        }
        if($this->validationType ==2){
             return $this->errorArray;
        }
    }

    public function validateItineraryHotelPriceForZeroValues($sessionData)
    {
        $this->validationType =2;
        $errorMsg = "Price for the hotel %hotel_name% is zero";
        $errorArray['warning'] = [];
        foreach ($sessionData['city'] as $index => $city) {
            foreach (array('optionone', 'optiontwo') as $option) {
                $hotel_tsid = array_get($city, "{$option}.hotel.hotel_tsid", null);
                if ($hotel_tsid && (int) $hotel_tsid > 0) {
                    $selectedHotelPrice = array_get($city, "{$option}.hotel.selectedHotelPrice", 0);
                    $selectedHotelName = array_get($city, "{$option}.hotel.hotel_name", '');
                    if (!((int)$selectedHotelPrice > 0)) {
                       $this->hasErrors = true;
                       $errorMsg = str_replace("%hotel_name%", $selectedHotelName, $errorMsg);
                       $errorArray['warning'][$index]['hotel'][$hotel_tsid]=[$errorMsg];
                    }
                }
            }
        }        
        $this->errorArray = $errorArray;
        return array_merge($sessionData,$errorArray);
    }

}
