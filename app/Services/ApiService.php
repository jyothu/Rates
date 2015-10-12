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

    function collectServicePrices($serviceTsIds, $startDate, $nights, $currency, $quantity)
    {
        $this->isRatesAvailableLocally = false;
        $service = $this->ratesRepository->getServiceByTsId($serviceTsIds); 
    	if ($service !== null) {
            $data = $this->ratesRepository->calculateTotalServiceRate($service, $startDate, $currency, $quantity, $nights);
            $this->isRatesAvailableLocally = true;
            return $data;
        }
    }

    function collectExtraPrices($serviceTsId, $startDate, $endDate, $currency, $quantity)
    {
        $this->isRatesAvailableLocally = false;
        $service = $this->ratesRepository->getServiceByTsId($serviceTsId); 
        if ($service !== null) {
            $data = $this->ratesRepository->calculateServiceExtraRate($service, $startDate, $endDate, $currency, $quantity);
            $this->isRatesAvailableLocally = true;
            return $data;
        }
    }
}