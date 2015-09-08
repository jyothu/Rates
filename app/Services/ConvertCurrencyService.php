<?php

namespace App\Services;

use Compassites\CurlHelper\CurlHelper;
/**
* 
*/

class ConvertCurrency
{
	private $currencyUrl = "http://api.fixer.io/latest";

	function __construct( CurlHelper $curlHelper )
	{
		$this->curlHelper = $curlHelper;
	}

	function exchangeRate( $base, $symbol )
	{
        $inputs = array("base" => $base, "symbols" => $symbol);
        $this->curlHelper->isMethodPost = false;
        $this->curlHelper->shouldAuthenticate = false;
        $jsonResponse = $this->curlHelper->sendCurlRequest( $inputs, $this->currencyUrl );
        $response = json_decode( $jsonResponse );
        return array_key_exists("error", $response) ? 1 : $response->rates->$symbol;
	}

}

