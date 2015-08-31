<?php

namespace App\Models;

class ExchangeRate extends \Eloquent
{
    protected $fillable = array('from_currency', 'to_currency', 'rate', 'status', 'id');
}
