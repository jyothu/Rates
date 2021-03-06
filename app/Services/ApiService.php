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

    public function pullRatesFromTravelStudio($funcName, $params)
    {
        $servicePrice = 0;
        $responses = [];
        $carbonStartDate = Carbon::parse($params["IncomingRequest"]["START_DATE"]);
        $totalNights = $params["IncomingRequest"]["NUMBER_OF_NIGHTS"];
        
        if ($totalNights == 1) {
            $response = $this->ratesFromTravelStudio($funcName, $params);
        } else {
            $params["IncomingRequest"]["NUMBER_OF_NIGHTS"] = 1;
            
            for ($noOfNights = 0; $noOfNights < $totalNights; $noOfNights++) {
                $params["IncomingRequest"]["START_DATE"] = $carbonStartDate->addDays($noOfNights)->format('Y-m-d');
                $responses[] = $this->ratesFromTravelStudio($funcName, $params);

                if ($this->isServiceNotFound) {
                    break;
                }
            }

            if ($this->isServiceNotFound) {
                return end($responses);
            }

            $response = $this->mergeResponses($responses);
        }

        return $response;
    }
    
    private function ratesFromTravelStudio($funcName, $params, $isPreviousYear=false, $count=0) {
        $response = $this->travelStudioService->pullRates($funcName, $params);
        $this->getErrorsFromResponse($response);

        if ($this->isServiceNotFound && $count == 0) {
            $params["IncomingRequest"]["START_DATE"] = Carbon::parse($params["IncomingRequest"]["START_DATE"])->subYear(1)->format('Y-m-d');
            $this->ratesFromTravelStudio($funcName, $params, true, $count++);
        }
        
        if (!$this->isServiceNotFound) {
            $this->incrementPricesForPreviousYearAndSetTotalPrices($response, $isPreviousYear);
        }

        return $response;
    }

    private function mergeResponses($responses) {
        $availableOptions = call_user_func_array('array_intersect', $this->serviceOptions);
        $originalResponse = $responses[0];

        foreach ($responses as $response) {
            $optionArray = $response->GetServicesPricesAndAvailabilityResult->Services->PriceAndAvailabilityService->ServiceOptions->PriceAndAvailabilityResponseServiceOption;
            if (is_array($optionArray)) {
                foreach ($optionArray as $key => $option) {
                    if (in_array($option->ServiceOptionName, $availableOptions)) {
                        $option->TotalSellingPrice = $this->totalPrices[$option->ServiceOptionName]["SellPrice"];
                        $option->TotalBuyingPrice = $this->totalPrices[$option->ServiceOptionName]["BuyPrice"];
                    }
                }
            } else {
                if (in_array($optionArray->ServiceOptionName, $availableOptions)) {
                    $optionArray->TotalSellingPrice = $this->totalPrices[$optionArray->ServiceOptionName]["SellPrice"];
                    $optionArray->TotalBuyingPrice = $this->totalPrices[$optionArray->ServiceOptionName]["BuyPrice"];
                }
            }
            $originalResponse->GetServicesPricesAndAvailabilityResult->Services->PriceAndAvailabilityService->ServiceOptions->PriceAndAvailabilityResponseServiceOption = $optionArray;
        }

        return $originalResponse;
    }

    private function incrementPricesForPreviousYearAndSetTotalPrices($response, $isPreviousYear) {

        $priceArray = $response->GetServicesPricesAndAvailabilityResult->Services->PriceAndAvailabilityService->ServiceOptions->PriceAndAvailabilityResponseServiceOption;
        if (is_array($priceArray)) {
            $this->serviceOptions[] = $this->collateServiceOptions($priceArray);
            foreach ($response->GetServicesPricesAndAvailabilityResult->Services->PriceAndAvailabilityService->ServiceOptions->PriceAndAvailabilityResponseServiceOption as $option) {
                if ($isPreviousYear) {
                    $option->TotalSellingPrice *= $this->incrementForPrevYear;
                    $option->TotalBuyingPrice *= $this->incrementForPrevYear;
                    $option->Prices->PriceAndAvailabilityResponsePricing->SellPrice *= $this->incrementForPrevYear;
                    $option->Prices->PriceAndAvailabilityResponsePricing->BuyPrice *= $this->incrementForPrevYear;
                }

                if (!isset($this->totalPrices[$option->ServiceOptionName]["BuyPrice"])) {
                    $this->totalPrices[$option->ServiceOptionName]["BuyPrice"] = 0;
                    $this->totalPrices[$option->ServiceOptionName]["SellPrice"] = 0;
                }

                $this->totalPrices[$option->ServiceOptionName]["BuyPrice"] += $option->TotalBuyingPrice;
                $this->totalPrices[$option->ServiceOptionName]["SellPrice"] += $option->TotalSellingPrice;
            }
        } else {
            $this->serviceOptions[] = [$response->GetServicesPricesAndAvailabilityResult->Services->PriceAndAvailabilityService->ServiceOptions->PriceAndAvailabilityResponseServiceOption->ServiceOptionName];
            if ($isPreviousYear) {
                $response->GetServicesPricesAndAvailabilityResult->Services->PriceAndAvailabilityService->ServiceOptions->PriceAndAvailabilityResponseServiceOption->TotalSellingPrice *= $this->incrementForPrevYear;
                $response->GetServicesPricesAndAvailabilityResult->Services->PriceAndAvailabilityService->ServiceOptions->PriceAndAvailabilityResponseServiceOption->TotalBuyingPrice *= $this->incrementForPrevYear;
                $response->GetServicesPricesAndAvailabilityResult->Services->PriceAndAvailabilityService->ServiceOptions->PriceAndAvailabilityResponseServiceOption->Prices->PriceAndAvailabilityResponsePricing->BuyPrice *= $this->incrementForPrevYear;
                $response->GetServicesPricesAndAvailabilityResult->Services->PriceAndAvailabilityService->ServiceOptions->PriceAndAvailabilityResponseServiceOption->Prices->PriceAndAvailabilityResponsePricing->SellPrice *= $this->incrementForPrevYear;
            }
            
            if (!isset($this->totalPrices[$response->GetServicesPricesAndAvailabilityResult->Services->PriceAndAvailabilityService->ServiceOptions->PriceAndAvailabilityResponseServiceOption->ServiceOptionName]["BuyPrice"])) {
                $this->totalPrices[$response->GetServicesPricesAndAvailabilityResult->Services->PriceAndAvailabilityService->ServiceOptions->PriceAndAvailabilityResponseServiceOption->ServiceOptionName]["BuyPrice"] = 0;
                $this->totalPrices[$response->GetServicesPricesAndAvailabilityResult->Services->PriceAndAvailabilityService->ServiceOptions->PriceAndAvailabilityResponseServiceOption->ServiceOptionName]["SellPrice"] = 0;
            }
            
            $this->totalPrices[$response->GetServicesPricesAndAvailabilityResult->Services->PriceAndAvailabilityService->ServiceOptions->PriceAndAvailabilityResponseServiceOption->ServiceOptionName]["BuyPrice"] += $response->GetServicesPricesAndAvailabilityResult->Services->PriceAndAvailabilityService->ServiceOptions->PriceAndAvailabilityResponseServiceOption->TotalBuyingPrice;
            $this->totalPrices[$response->GetServicesPricesAndAvailabilityResult->Services->PriceAndAvailabilityService->ServiceOptions->PriceAndAvailabilityResponseServiceOption->ServiceOptionName]["SellPrice"] += $response->GetServicesPricesAndAvailabilityResult->Services->PriceAndAvailabilityService->ServiceOptions->PriceAndAvailabilityResponseServiceOption->TotalSellingPrice;
        }
        return $response;
    }

    private function collateServiceOptions($optionArray) {
        $serviceOptions = array_reduce($optionArray, function ($result, $option) {
            $result[] = $option->ServiceOptionName;
            return $result;
        }, array());
        return $serviceOptions;
    }

    private function getErrorsFromResponse($response)
    {
        $hasServicePriceAndAvailabilityKey = property_exists($response, 'GetServicesPricesAndAvailabilityResult');
        $hasErrorKeyCount = $hasServicePriceAndAvailabilityKey && property_exists($response->GetServicesPricesAndAvailabilityResult, 'Errors') && property_exists($response->GetServicesPricesAndAvailabilityResult->Errors, "Error");
        
        if ($hasErrorKeyCount || !$hasServicePriceAndAvailabilityKey) {
            $this->isServiceNotFound = true;
        } else {
            $this->isServiceNotFound = false;
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