<?php

namespace App\Services;

use App;
use Carbon\Carbon;

class TravelStudioService
{
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

    public function pullRatesFromTravelStudio($funcName, $params)
    {
        return $this->soapClient()->$funcName($params);
    }
}
