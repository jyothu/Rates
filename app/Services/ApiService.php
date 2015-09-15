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

    function collectServicePrices( $serviceTsIds, $startDate, $nights, $currency, $quantity )
    {
    	$this->isRatesAvailableLocally = false;
        $service = $this->ratesRepository->getServiceByTsId($serviceTsIds); 
    	if ($service !== null) { 
            $endDate = Carbon::parse($startDate)->addDays($nights)->format('Y-m-d');
            $data = $this->ratesRepository->calculateTotalServiceRate($service->id, $startDate, $endDate, $currency, $quantity);
            $this->isRatesAvailableLocally = true;
            return $data;
        }
    }
}