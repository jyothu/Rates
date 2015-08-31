<?php

namespace Compassites\TsBookingIdPoolHelper;

use Illuminate\Support\ServiceProvider;

class TsBookingIdPoolHelperServiceProvider extends ServiceProvider {

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
        $this->app->singleton('Compassites\TsBookingIdPoolHelper\TsBookingIdPoolHelper', function($app) {
            return new TsBookingIdPoolHelper($app['Illuminate\Session\Store'], $app['TsBookingIdPool']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides() {
         return ['Compassites\TsBookingIdPoolHelper\TsBookingIdPoolHelper'];
    }

}
