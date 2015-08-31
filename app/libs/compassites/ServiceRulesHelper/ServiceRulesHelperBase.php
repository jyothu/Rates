<?php

/*
 * This file is part of TravelBuilder v0.4 project
 */

namespace Compassites\ServiceRulesHelper;

use Compassites\EnvironmentHelper\EnvironmentHelper;
//use Compassites\ServiceRulesHelper\ServiceRulesHelper;

/**
 * It helps to save itinerary from 
 * session data which comprises of cities, hotels, arrival details, internal services,
 * car type, tour manager etc..
 * 
 * @package SaveRulesHelperBase
 * @author VIPIN PS <vipinps13@gmail.com>
 * @version 1.0
 */
class ServiceRulesHelperBase
{
            protected $etruleApiUrl = 'http://localhost/dev-rules/public/index.php/checkExclusion';
        
            public function __construct(DateHelper $DateHelper ,\Itinerary $Itinerary ,EnvironmentHelper $environmentHelper)
            {   
                    $this->environmentHelper = $environmentHelper;
                    $this->dateHelper = $DateHelper;
                    $this->itinerary = $Itinerary;
//                    $this->etruleApiUrl = $this->environmentHelper->rule_url;
//                     if ($environmentHelper->hasEnvironment()) {
//                         $this->etruleApiUrl =  $this->environmentHelper->rule_url;
//                     }
            }
              	public  function ruleCheck($data)
	{       
                        $this->etruleApiUrl = $this->environmentHelper->rule_url;
                        $fields = array('jsonData'=>$data);		
                        $server_output  = $this->curlRequest($this->etruleApiUrl,$fields);
                        $newarray = json_decode($server_output);		
                        return $newarray;
	}


	public function  curlRequest($url,$fields)
	{
		$fields_string='';
		foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
		rtrim($fields_string, '&');	
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$fields_string);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$server_output = curl_exec ($ch);
		curl_close ($ch);
		return $server_output;
	}



}
