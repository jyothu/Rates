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

    function collectServicePrices( $serviceIds, $startDate, $nights, $currency, $quantity )
    {
    	$this->isRatesAvailableLocally = false;
    	if (($this->ratesRepository->getServiceById($serviceIds) !== null)) { 
            $endDate = Carbon::parse($startDate)->addDays($nights)->format('Y-m-d');
            $data = $this->ratesRepository->calculateTotalServiceRate($serviceIds, $startDate, $endDate, $currency, $quantity);
            $this->isRatesAvailableLocally = true;
            return $data;
        }
    }
}