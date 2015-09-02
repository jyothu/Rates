<?php

namespace Compassites\TravelStudioClient;

use Illuminate\Support\ServiceProvider;

class TravelStudioClientServiceProvider extends ServiceProvider {

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
    public function register() {
        $this->app->singleton('Compassites\TravelStudioClient\TravelStudioClient', function($app) {
            return new TravelStudioClient($app['DateHelper'],$app['Illuminate\Session\Store'], $app['ServiceRulesHelper']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides() {
         return ['Compassites\TravelStudioClient\TravelStudioClient'];
    }

}
