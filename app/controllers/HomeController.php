<?php

class HomeController extends BaseController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function showLanding()
    {
        return View::make('pages.landing');
    }

    public function showRates()
    {
        return View::make('pages.rates');
    }
}
