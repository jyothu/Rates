<?php

namespace Compassites\CreateItinerarySessionHelper;

use Illuminate\Support\ServiceProvider;

class CreateItinerarySessionHelperServiceProvider extends ServiceProvider
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
        $this->app->singleton('Compassites\CreateItinerarySessionHelpe\CreateItinerarySessionHelper', function ($app) {
            return new CreateItinerarySessionHelper($app['Illuminate\Session\Store'], $app['DateHelper'], $app['Itinerary'],$app['TravelStudio'],$app['ItineraryValidationHelper']);
        });

    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['Compassites\CreateItinerarySessionHelper\CreateItinerarySessionHelper'];
    }

}
