<?php

namespace Compassites\CurlHelper;

use Illuminate\Support\ServiceProvider;

class CurlHelperServiceProvider extends ServiceProvider {

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
        $this->app->singleton('Compassites\CurlHelper\CurlHelper', function($app) {
            return new CurlHelper();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides() {
         return ['Compassites\CurlHelper\CurlHelper'];
    }

}
