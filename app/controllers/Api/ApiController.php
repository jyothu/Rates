<?php

namespace App\Controllers;

use BaseController;
use Input;
use Validator;
use Response;
use App\Repositories\RatesRepository;

class ApiController extends BaseController
{
    public $serviceRules = [
            'service_id' => 'required|exists:services,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date'
        ];

    public function __construct(RatesRepository $ratesRepo)
    {
        $this->ratesRepo = $ratesRepo;
    }

    public function getServicePrice()
    {
        $requestData = Input::all();
        $validator = Validator::make($requestData, $this->serviceRules);
        if ($validator->fails()) {
            return Response::json(['error' => $validator->errors()->all()], 400);
        }

        $data = $this->ratesRepo->calculateTotalServiceRate($requestData['service_id'], $requestData['start_date'], $requestData['end_date']);
        return Response::json(['success' => $data]);
    }
}
