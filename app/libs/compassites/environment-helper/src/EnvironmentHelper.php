<?php

namespace Compassites\EnvironmentHelper;

use GlobalSettingsEnvironment;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of EnvironmentHelper
 *
 * @author jeevan
 */
class EnvironmentHelper
{

    protected $hostName;
    protected $globalSetting;
    protected $currentEnvironment;
    public $tsLicenseKey;
    public $tsApiEndpoint;
    public $tmUrl;
    public $advancedTMBUrl;
    public $rule_url;

    public function __construct(GlobalSettingsEnvironment $globalSettingsEnvironment)
    {
        $this->hostName = gethostname();
        $this->currentEnvironment = $globalSettingsEnvironment->getCurrentGlobalSettingEnvironmentFromHostName($this->hostName);
        $this->prepareEnvironmentData();
    }

    public function hasEnvironment()
    {
        if ($this->currentEnvironment) {
            return true;
        }
        return false;
    }

    protected function prepareEnvironmentData()
    {
        foreach ($this->currentEnvironment->globalSettings as $setting) {
           
            if ($setting->globalSettingsParameter->parameter_name == 'ts_api_endpoint') {
                $this->tsApiEndpoint = $setting->value;
            }
            if ($setting->globalSettingsParameter->parameter_name == 'ts_license_key') {
                $this->tsLicenseKey = $setting->value;
            }
            if ($setting->globalSettingsParameter->parameter_name == 'tm_url') {
                $this->tmUrl = $setting->value;
            }
            if ($setting->globalSettingsParameter->parameter_name == 'advanced_tmb_url') {
                $this->advancedTMBUrl = $setting->value;
            }
            if ($setting->globalSettingsParameter->parameter_name == 'rule_url') {
                $this->rule_url = $setting->value;
            }
        }
    }

}
