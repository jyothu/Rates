<?php

use App\Models\Service;
use Carbon\Carbon;
use App\Repositories\RatesRepository;

class RateController extends BaseController
{
    public function __construct(RatesRepository $ratesRepo)
    {
        parent::__construct();
        $this->ratesRepo = $ratesRepo;
    }

    public function getOptions()
    {
        $serviceId = Input::get('service');
        $service = $this->ratesRepo->getServiceByTsId($serviceId);
        $serviceOptions = isset( $service ) ? $service->service_options : [];
        if (empty($serviceOptions)) {
            return ['error' => 'Invalid Service ID'];
        }
        //returns html of options as string just like echo
        return View::make('partials.rateOptions')->with('serviceOptions', $serviceOptions)->render();
    }

    public function getRates()
    {
        $optionId = Input::get('option');
        $start = Input::get('checkin');
        $end = Input::get('checkout');

        $carbonEnd = Carbon::parse($end);

        $actualEnd = $carbonEnd->subDay()->format('Y-m-d');

        $startObj = new DateTime($start);
        $endObj = new DateTime($end);
        $interval = DateInterval::createFromDateString('1 day');
        $period = new DatePeriod($startObj, $interval, $endObj);

        $prices = $this->ratesRepo->getServiceRate($optionId, $start, $actualEnd);

        if (count($prices) == 1) {
            $nights = Carbon::parse($end)->diffInDays(Carbon::parse($start));
            $totalBuyingPrice = ($prices[0]->buy_price)*($nights);
            $totalSellingPrice = ($prices[0]->sell_price)*($nights);
        } else {
            $totalBuyingPrice = $totalSellingPrice = 0;

            foreach ($prices as $price) {
                $carbonStartDate = Carbon::parse($price->start);
                $carbonEndDate = Carbon::parse($price->end);
                
                foreach ($period as $date) {
                    if (Carbon::parse($date->format('Y-m-d'))->between($carbonStartDate, $carbonEndDate)) {
                        $totalBuyingPrice += $price->buy_price;
                        $totalSellingPrice += $price->sell_price;
                    }
                }
            }
        }
        // will return html of price table as string same as echo
        return View::make('partials.serviceRatesTable')->with('prices', $prices)->with('totalBuyingPrice', $totalBuyingPrice)->with('totalSellingPrice', $totalSellingPrice)->render();
    }
}
