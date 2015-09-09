<?php

namespace App\Repositories;
use App\Models\ExchangeRate;

class ExchangeRateRepository
{
	
	function exchangeRate($from, $to)
	{
        $rate = ExchangeRate::where('from_currency', '=', $from)->where('to_currency', '=', $to)->first();
        return isset($rate) ? $rate->rate : 1;
	}

}