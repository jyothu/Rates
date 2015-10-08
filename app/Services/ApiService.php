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
            $startDate = Carbon::parse($startDate)->format('Y-m-d');
            $endDate = Carbon::parse($startDate)->addDays($nights)->format('Y-m-d');
            $data = $this->ratesRepository->calculateTotalServiceRate($service->id, $startDate, $endDate, $currency, $quantity, $nights);
            $this->isRatesAvailableLocally = true;
            return $data;
        }
    }

    function collectExtraPrices( $serviceTsId, $startDate, $endDate, $currency, $quantity)
    {
        $this->isRatesAvailableLocally = false;
        $service = $this->ratesRepository->getServiceByTsId($serviceTsId); 
        if ($service !== null) {
            $startDate = Carbon::parse($startDate)->format('Y-m-d');
            $endDate = Carbon::parse($endDate)->format('Y-m-d');
            $data = $this->ratesRepository->calculateServiceExtraRate($service->id, $startDate, $endDate, $currency, $service->currency->code, $quantity);
            $this->isRatesAvailableLocally = true;
            return $data;
        }
    }
}