<?php

namespace App\Services;


/**
* 
*/
class ApiService
{
	
    public function __construct(App\Repositories\RatesRepository $ratesRepository, TravelStudioService $travelStudioService)
    {
        $this->ratesRepository = $ratesRepository;
        $this->travelStudioService = $travelStudioService;
    }

    function collectServicePrices( $serviceIds, $startDate, $nights, $currency )
    {
    	$this->isRatesAvailableLocally = false;
    	if (($this->ratesRepo->getServiceById($serviceIds) !== null)) {
            $endDate = Carbon::parse($startDate)->addDays($nights);
            $data = $this->ratesRepo->calculateTotalServiceRate($serviceIds, $startDate, $endDate, $currency);
            $this->isRatesAvailableLocally = true;
            return $data;
        }
    }
}