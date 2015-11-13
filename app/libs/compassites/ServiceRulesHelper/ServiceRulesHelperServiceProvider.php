<?php

namespace Compassites\ServiceRulesHelper;

use Illuminate\Support\ServiceProvider;

class ServiceRulesHelperServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('Compassites\ServiceRulesHelper\ServiceRulesHelper', function ($app) {
            //return new ServiceRulesHelper($app['Illuminate\Session\Store'], $app['DateHelper'], $app['Itinerary'], $app['ItineraryCity'], $app['ServiceOption'], $app['ItinararyService'], $app['InternalService'], $app['ItenararyInternalService'], $app['Service'], $app['Activity'], $app['ItenararyActivity'], $app['ServiceExtra']);
            return new ServiceRulesHelper($app['DateHelper'] , $app['Itinerary'],$app['EnvironmentHelper']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['Compassites\ServiceRulesHelper\ServiceRulesHelper'];
    }

}
