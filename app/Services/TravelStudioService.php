<?php

namespace App\Services;

class TravelStudioService
{
    public function __construct()
    {
        $this->curlHelper = App::make('Compassites\CurlHelper\CurlHelper');
    }

    public function createArrayForTS()
    {

    }

}
