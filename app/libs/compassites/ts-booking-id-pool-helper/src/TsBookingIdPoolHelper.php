<?php

namespace Compassites\TsBookingIdPoolHelper;

use Illuminate\Session\Store as Session;
use TsBookingIdPool;
use Illuminate\Support\Facades\URL;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of TsBookingIdPoolHelper
 *
 * @author jeevan
 */
class TsBookingIdPoolHelper
{

    //put your code here
    protected $session;
    protected $tsBookingIdPool;

    public function __construct(Session $session, TsBookingIdPool $tsBookingIdPool)
    {
        $this->session = $session;
        $this->tsBookingIdPool = $tsBookingIdPool;
    }

    public function setTsBookingIdInSession()
    {
        if (!$this->session->has('tsBookingId')) {
            $tsBookingId = $this->tsBookingIdPool->getAnAvailableTsBookingIdFromPool();
            $tsBookingIdPool = $this->tsBookingIdPool->getTsBookingIdPoolObjectFromTsBookingId($tsBookingId);
            $tsBookingIdPool->is_available = 0;
            $tsBookingIdPool->save();
            $this->session->put('tsBookingId', $tsBookingId);
        } else {
            if ($this->isBeingRedirectedAfterPushingToTm()) {
                $this->clearTsBookingIdSession();
            }
        }
    }

    public function getTsBookingIdFromSession()
    {
        if ($this->session->has('tsBookingId')) {
            return $this->session->get('tsBookingId');
        }
    }

    public function clearTsBookingIdSession()
    {
        if ($this->session->has('tsBookingId')) {
            $tsBookingIdPool = $this->tsBookingIdPool->getTsBookingIdPoolObjectFromTsBookingId($this->getTsBookingIdFromSession());
            $this->session->forget('tsBookingId');
            $tsBookingIdPool->is_available = 1;
            $tsBookingIdPool->save();
            $this->session->forget('isBeingRedirectedAfterPushingToTm');
        }
    }

    public function isBeingRedirectedAfterPushingToTm()
    {
        return $this->session->has('isBeingRedirectedAfterPushingToTm') && $this->session->get('isBeingRedirectedAfterPushingToTm');
    }

}
