<?php

namespace Compassites\SaveItineraryHelper;

use Illuminate\Support\ServiceProvider;

class SaveItineraryHelperServiceProvider extends ServiceProvider
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
        $this->app->singleton('Compassites\SaveItineraryHelper\SaveItineraryHelper', function ($app) {
            return new SaveItineraryHelper($app['Illuminate\Session\Store'], $app['DateHelper'], $app['Itinerary'], $app['ItineraryCity'], $app['ServiceOption'], $app['ItinararyService'], $app['InternalService'], $app['ItenararyInternalService'], $app['Service'], $app['Activity'], $app['ItineraryActivity'], $app['ServiceExtra'], $app['DataMapperHelper'],$app['PriceCalculation']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['Compassites\SaveItineraryHelper\SaveItineraryHelper'];
    }

}
