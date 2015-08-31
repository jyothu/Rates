<?php

namespace Compassites\DataMapperHelper;

use Illuminate\Support\ServiceProvider;

class DataMapperHelperServiceProvider extends ServiceProvider {

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
        $this->app->singleton('Compassites\DataMapperHelper\DataMapperHelper', function($app) {
            return new DataMapperHelper($app['DateHelper']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides() {
         return ['Compassites\DataMapperHelper\DataMapperHelper'];
    }

}
