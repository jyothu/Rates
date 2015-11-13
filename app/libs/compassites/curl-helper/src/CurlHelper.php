<?php

namespace Compassites\CurlHelper;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CurlHelper
 *
 * @author jeevan
 */
class CurlHelper
{

    public $apiRequestUrl;
    public $isMethodPost = true;
    public $httpResponse;
    public $shouldAuthenticate = true;

    function sendCurlRequest($dataToSend, $apiRequestUrl)
    {
        if ($apiRequestUrl) {
            $this->apiRequestUrl = $apiRequestUrl;
        }
        $curlData = http_build_query($dataToSend);
        $ch = curl_init();
        if ($this->shouldAuthenticate) {
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, "travelmaker:tr@velm@ker");
        }
        if($this->isMethodPost){
            curl_setopt($ch, CURLOPT_URL, $this->apiRequestUrl);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $curlData);
        } else {
            curl_setopt($ch, CURLOPT_URL, $this->apiRequestUrl."?".$curlData);
        }
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_POST, $this->isMethodPost);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        return $this->httpResponse = curl_exec($ch);
    }

}
