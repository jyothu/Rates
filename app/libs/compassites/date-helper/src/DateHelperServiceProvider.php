<?php

namespace Compassites\DateHelper;

use Illuminate\Support\ServiceProvider;

class DateHelperServiceProvider extends ServiceProvider {

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
        $this->app->singleton('Compassites\DateHelper\DateHelper', function($app) {
            return new DateHelper();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides() {
         return ['Compassites\DateHelper\DateHelper'];
    }

}
