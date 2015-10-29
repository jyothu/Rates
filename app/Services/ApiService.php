<?php

namespace App\Services;

use App\Repositories\RatesRepository;
use Carbon\Carbon;
/**
* 
*/
class ApiService
{
	
    public function __construct(RatesRepository $ratesRepository, TravelStudioService $travelStudioService)
    {
        $this->ratesRepository = $ratesRepository;
        $this->travelStudioService = $travelStudioService;
    }

    function collectServicePrices($serviceTsIds, $startDate, $nights, $currency, $rooms)
    {
        $this->isRatesAvailableLocally = false;
        $service = $this->ratesRepository->getServiceByTsId($serviceTsIds); 
    	if ($service !== null) {
            $roomsWithOccupancy = $this->sanitizeRoomDetails($rooms);
            $data = $this->ratesRepository->calculateTotalServiceRate($service, $startDate, $currency, $roomsWithOccupancy, $nights);
            $this->isRatesAvailableLocally = true;
            return $data;
        }
    }

    function collectExtraPrices($serviceTsId, $startDate, $endDate, $currency, $noOfPeople)
    {
        $this->isRatesAvailableLocally = false;
        $service = $this->ratesRepository->getServiceByTsId($serviceTsId); 
        if ($service !== null) {
            $data = $this->ratesRepository->calculateServiceExtraRate($service, $startDate, $endDate, $currency, $noOfPeople);
            $this->isRatesAvailableLocally = true;
            return $data;
        }
    }

    private function sanitizeRoomDetails($rooms) {
        $sanitizedArray = array_reduce($rooms, function($result, $a) { 
            $result[$a["OCCUPANCY"]] = array("QUANTITY" => $a["QUANTITY"], "NO_OF_PASSENGERS" => $a["NO_OF_PASSENGERS"]); 
            return $result;
        }, array() );

        return $sanitizedArray;
    }
}