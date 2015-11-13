<?php

namespace Compassites\DateHelper;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of DateHelper
 *
 * @author jeevan
 */
class DateHelper
{

    //put your code here
    function getMySqlDateFromNormalDate($date)
    {
        if (strpos($date, "-") > 0) {
            if (count(explode("-", $date)) == 3) {
                return $date;
            }
        }
        $startDate = explode("/", $date);
        return $date ? $startDate[2] . "-" . $startDate[0] . "-" . $startDate[1] : null;
    }

    function getNormalDateFromMySqlDate($date)
    {
        if (strpos($date, ":") > 0) {
            list($date, $time) = explode(" ", $date);
        }
        $startDate = explode("-", $date);
        return $date ? $startDate[1] . "/" . $startDate[2] . "/" . $startDate[0] : null;
    }

    function getMySqlDateTimeFromNormalDate($date)
    {
        return $date ? $this->getMySqlDateFromNormalDate($date) . " 00:00:00" : null;
    }

    function addDaysToDate($date, $numberOfDays)
    {
        return $numberOfDays > 0 ? date('Y-m-d', strtotime($date . ' + ' . $numberOfDays . ' days')) : $date;
    }

    function dateDifferenceInDays($date1, $date2)
    {
        $datetime1 = new \DateTime($date1);
        $datetime2 = new \DateTime($date2);
        $interval = $datetime1->diff($datetime2);
        return $interval->days;
    }

    function removeTimeFromTMDate($dateTime)
    {
        list($date, $time) = explode("T", $dateTime);
        return $date;
    }

    function removeTimeFromMysqlDateTime($datetime)
    {
        return substr($datetime, 0, 10);
    }

    function addDaysToNormalDate($dateOnWhichServiceIsRequired, $days = 0)
    {
        $dateOnWhichServiceIsRequired = $this->getMySqlDateFromNormalDate($dateOnWhichServiceIsRequired);
        $dateOnWhichServiceIsRequired = $this->addDaysToDate($dateOnWhichServiceIsRequired, $days);
        $dateOnWhichServiceIsRequired = $this->getNormalDateFromMySqlDate($dateOnWhichServiceIsRequired);
        return $dateOnWhichServiceIsRequired;
    }
    function getPreviousYearDateFromNormalDate($date) {
        $date = $this->getMySqlDateFromNormalDate($date);
        return date('m/d/Y', strtotime($date . '-1 year'));
    }
}
