<?php

namespace App\Services;

use App;
use Carbon\Carbon;

class TravelStudioService
{
    protected $isServiceNotFound = false;
    protected $incrementForPrevYear = 1.05;
    protected $serviceOptions = [];
    protected $totalPrices = [];

    public function __construct()
    {
        $this->curlHelper = App::make('Compassites\CurlHelper\CurlHelper');
    }

    public function soapClient()
    {
        $params = array(
            "soap_version" => SOAP_1_2,
            "trace" => 1,
            "exceptions" => 1,
        );
        $client = new \SoapClient('http://52.74.9.44/B2CWS/B2CXMLAPIWebService.asmx?WSDL', $params);
        return $client;
    }

    public function tsExchangeRates()
    {
        return $this->soapClient()->GetExchangeRates();
    }

    public function pullRates($funcName, $params) {
        return $this->soapClient()->$funcName($params);
    }

}
