<?php

namespace Enchanting\MigrateTs\Models;

class ServiceType extends \Eloquent
{
    
    protected $fillable = array('name', 'id');

    public function services()
    {

        return $this->hasMany('Enchanting\MigrateTs\Models\Service');

    }
}
