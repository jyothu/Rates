<?php

namespace App\Services;

/**
* 
*/

class ConvertCurrency
{
	private $currencyUrl = "http://api.fixer.io/latest?";

	function __construct( Compassites\CurlHelper\CurlHelper $curlHelper )
	{
		$this->curlHelper = $curlHelper;
	}

	function exchangeRate( $base, $symbol )
	{
       $inputs = array("base" => $base, "symbol" => $symbol);
       $jsonResponse = $curlHelper->sendCurlRequest( $inputs, $currencyUrl );
       $response = json_decode( $jsonResponse );
       return array_key_exists("error", $response) ? 1 : $response["rates"][$symbol];
	}

}

