<?php

namespace Compassites\PriceCalculation;

use Illuminate\Support\ServiceProvider;

class PriceCalculationServiceProvider extends ServiceProvider {

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register() {
        $this->app->singleton('Compassites\PriceCalculation\PriceCalculation', function($app) {
            return new PriceCalculation($app['TravelStudio'], $app['DateHelper'], $app['GeneralSetting']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides() {
         return ['Compassites\PriceCalculation\PriceCalculation'];
    }

}
