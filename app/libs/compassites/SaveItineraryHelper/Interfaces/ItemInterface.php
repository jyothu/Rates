<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Compassites\SaveItineraryHelper\Interfaces;

/**
 *
 * @author jeevan
 */
interface ItemInterface
{

    function hasItemInSession($itemName);

    function getItemFromSession($itemName);

    function mapItemDbFieldsToSessionArrayKeys();

    function getItemKeyValuePairForInsertingIntoDb();

    function saveItem();
}
