<?php

namespace Compassites\ItineraryValidationHelper;

use Illuminate\Support\ServiceProvider;

class ItineraryValidationHelperServiceProvider extends ServiceProvider
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
        $this->app->singleton('Compassites\ItineraryValidationHelper\ItineraryValidationHelper', function ($app) {
            return new ItineraryValidationHelper( $app['DateHelper']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['Compassites\ItineraryValidationHelper\ItineraryValidationHelper'];
    }

}
