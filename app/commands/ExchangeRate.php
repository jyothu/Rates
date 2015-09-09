<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ExchangeRate extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'exchange_rates';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Get exchange rates from Travel Studio';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		$travelStudioService = App::make('App\Services\TravelStudioService');
		$tsRates = $travelStudioService->tsExchangeRates();

		$xmlResponse = (simplexml_load_string($tsRates->GetExchangeRatesResult->any));
        $exchangeRates = $xmlResponse->NewDataSet;  
		foreach ($exchangeRates as $key => $rates) {
			foreach ($rates as $rate) {
				$params = array('from_currency' => $rate->FROMCURRENCY, 'to_currency' => $rate->TOCURRECNY, 'rate' => $rate->EXCHANGERATEVALUE);
				App\Models\ExchangeRate::create($params);
			}
		}

	}

}