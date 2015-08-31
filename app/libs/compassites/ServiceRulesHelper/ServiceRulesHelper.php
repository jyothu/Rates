<?php

/*
 * This file is part of TravelBuilder v0.4 project
 */

namespace Compassites\ServiceRulesHelper;
use Compassites\DateHelper\DateHelper;
use Compassites\EnvironmentHelper\EnvironmentHelper;
/**
 * It helps to save itinerary from
 * session data which comprises of cities, hotels, arrival details, internal services,
 * car type, tour manager etc..
 *
 * @package ServiceRulesHelper
 * @author VIPIN PS <vipinps13@gmail.com>
 * @version 1.0
 */
class ServiceRulesHelper extends ServiceRulesHelperBase
{

    /**
     * Holds Session service which can be used to access/modify session data
     * @var Session
     * @access protected
     */
        protected $DateHelper;
        protected $Service;
    /**
     * Model object for itinerary
     * @var \Itinerary
     */


    /**
     * Initialises services required for saving itinerary data
     * @param Store $session To hold session service object
     */
    public function __construct(DateHelper $DateHelper ,\Itinerary $Itinerary ,EnvironmentHelper $environmentHelper)
    {

        $this->dateHelper = $DateHelper;
        $this->itinerary = $Itinerary;
        $this->environmentHelper = $environmentHelper;
    }


    public  function getRulesBysingleService($data)
    {        $requestArray = array();

            foreach ($data as $key => $data) {

                $currentEndDate= '';
                $requestArray[$key]['ts_id']   =  $data['ts_id'];
                $requestArray[$key]['region_id']   = $data['region_id'];
                $requestArray[$key]['from_date']   = $this->dateHelper->getMySqlDateFromNormalDate($data['start_date']);
                $nights = (int)$data['nights']>0?$data['nights']-1:0;
                $currentEndDate = $this->dateHelper->addDaysToDate($data['start_date'],$nights);
                $requestArray[$key]['to_date']   = $currentEndDate;
            }
            return $ruleCheckResult =       $this->ruleCheck(json_encode($requestArray));

    }


    public function  getRulesForUpdate($ruleCheckFinalArray)
    {
        $ruleCheckResult =       $this->ruleCheck(json_encode($ruleCheckFinalArray));
            $ruleCheckFinalResult  = array();
            if($ruleCheckResult){
                foreach ($ruleCheckResult as $key => $ruleCheckResult) {
                    if($ruleCheckResult->response=='error') {
                        array_push($ruleCheckFinalResult,$ruleCheckResult );
                    }
                }

            }
          return $ruleCheckFinalResult;
    }


    public function prepareErrorMessages($data)
    {
        $ruleCheckMsgResult  = array();
        $message = '';
        $i=1;
        foreach ($data as $key => $value) {
              $message .= $value->rule_name.' - '.$value->response_message.'<br>';
              $i++;
        }
          return $message;
    }

}
